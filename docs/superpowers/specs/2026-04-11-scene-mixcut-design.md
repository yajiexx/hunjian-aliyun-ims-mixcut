# Scene Mixcut Design

Date: 2026-04-11
Status: Approved in conversation, pending written spec review

## Summary

Add a reusable scene-based mixcut flow for multi-shot video composition. The new flow should accept a front-end friendly `scenes[]` payload, normalize loose input shapes, assemble a timeline automatically, and submit the result through the existing IMS integration.

This design targets editor-style usage where:

- One video is composed from multiple scenes
- Each scene can contain one or more materials
- Each scene can define dubbing, subtitles, word art, and transitions
- Scene-relative timing is converted to absolute timeline timing by the backend

## Goals

- Provide a stable backend entry for scene-based mixcut requests
- Keep the request schema close to the editor UI structure
- Reuse the current `Timeline`, `VideoTrack`, `AudioTrack`, `SubtitleTrack`, and effect models
- Support both prepared dubbing audio and backend-generated TTS
- Support ordinary subtitles and word art as separate concepts
- Allow the request schema to evolve without forcing template rewrites

## Non-Goals

- Remote media metadata probing in the first version
- Automatic subtitle sentence splitting
- Complex multi-track parallel editing inside a scene
- Smart duration completion such as freeze-frame, gap filling, or AI retiming
- A dedicated sticker model separate from subtitle-based word art

## Existing Foundation

The current package already provides the main building blocks required by this feature:

- `TimelineBuilder` and `MixcutTemplateBuilder` for timeline and output assembly
- `VideoTrackClip` for scene materials and clip-local effects
- `AudioTrackClip` and `AudioBuilder` for BGM, raw audio clips, and `AI_TTS`
- `SubtitleTrackClip` and `SubtitleBuilder` for subtitle rendering
- `Transition`, `Filter`, `VFX`, `KenBurns`, and other clip-level effects
- `MediaProducingService` for submission and job polling

The missing part is a scene-oriented orchestration layer that turns editor payloads into the existing timeline model.

## Chosen Approach

Create a new scene-based template path instead of expanding the existing narration template or pushing all logic into the service layer.

The new flow will introduce:

- `ScenePayloadNormalizer`
- `SceneDurationResolver`
- `SceneTimelineAssembler`
- `SceneMixcutTemplate`
- `MediaProducingService::submitSceneMixcut(array $context, array $options = array())`

This keeps responsibilities separate:

- Normalization and compatibility handling live in one place
- Timeline assembly rules live in one place
- Template output remains compatible with current package patterns
- Service code stays thin and testable

## High-Level Flow

1. The caller passes a scene-based context payload.
2. `ScenePayloadNormalizer` rewrites the payload into a stable internal shape.
3. `SceneDurationResolver` determines the duration of each scene.
4. `SceneTimelineAssembler` converts normalized scenes into tracks and clips.
5. `SceneMixcutTemplate` returns:
   - `timeline`
   - `outputMediaConfig`
6. `MediaProducingService::submitSceneMixcut()` submits the built result through the existing client.

## Request Schema

The top-level structure is centered around `scenes[]`.

```php
array(
    'canvas' => array(
        'width' => 1080,
        'height' => 1920,
    ),
    'outputMediaConfig' => OutputMediaConfig::oss('oss://bucket/output/demo.mp4'),
    'global' => array(
        'bgm' => 'oss://bucket/bgm.mp3',
        'watermark' => 'oss://bucket/logo.png',
        'subtitleStyle' => array(
            'font' => 'Alibaba PuHuiTi 2.0',
            'fontSize' => 50,
            'fontColor' => '#FFFFFF',
            'boxColor' => '#101820',
        ),
        'wordArtStyle' => array(
            'preset' => 'orange-pop',
        ),
        'sceneTransition' => array(
            'type' => 'fade',
            'duration' => 0.4,
        ),
    ),
    'scenes' => array(
        array(
            'sceneId' => 'scene-1',
            'sceneDuration' => 4.0,
            'transition' => array(
                'type' => 'fade',
                'duration' => 0.5,
            ),
            'materials' => array(
                array(
                    'materialId' => 'scene-1-m1',
                    'type' => 'video',
                    'url' => 'oss://bucket/a.mp4',
                    'sourceRange' => array('in' => 0.0, 'out' => 2.0),
                    'sceneRange' => array('start' => 0.0, 'end' => 2.0),
                    'duration' => 2.0,
                    'layout' => array(
                        'x' => 0,
                        'y' => 0,
                        'width' => 1080,
                        'height' => 1920,
                        'adaptMode' => 'Cover',
                    ),
                ),
                array(
                    'materialId' => 'scene-1-m2',
                    'type' => 'image',
                    'url' => 'oss://bucket/b.jpg',
                    'duration' => 2.0,
                    'transition' => array(
                        'type' => 'directional-left',
                        'duration' => 0.3,
                    ),
                ),
            ),
            'dubbing' => array(
                'audioUrl' => 'oss://bucket/tts/scene-1.mp3',
                'text' => '这是镜头一配音文案',
                'voice' => 'zhitian_emo',
                'duration' => 4.0,
                'speechRate' => 0,
                'pitchRate' => 0,
            ),
            'subtitles' => array(
                array(
                    'text' => '这是普通字幕',
                    'start' => 0.0,
                    'end' => 2.0,
                    'referenceMaterialId' => 'scene-1-m1',
                ),
            ),
            'wordArts' => array(
                array(
                    'text' => '重点来了',
                    'start' => 1.0,
                    'end' => 2.5,
                    'preset' => 'orange-pop',
                    'layout' => array(
                        'x' => 120,
                        'y' => 360,
                        'width' => 400,
                        'height' => 120,
                        'alignment' => 'Center',
                    ),
                ),
            ),
        ),
    ),
);
```

## Schema Rules

- `scenes` is the only required top-level business field
- Each scene always uses `materials[]`, even if it contains one material
- Scene timing fields use scene-relative time
- The backend converts all relative scene timing to absolute timeline timing
- `sceneDuration` is optional and overrides derived duration when present
- `dubbing.duration` is the scene dubbing duration hint for version 1
- `dubbing.audioUrl` takes precedence over `dubbing.text + dubbing.voice`
- Ordinary subtitles and word art are modeled separately
- Scene-level transitions and material-level transitions are both supported
- Transition precedence is `materials[].transition` over `scenes[].transition` over `global.sceneTransition`
- Global defaults can be overridden at scene level and item level
- Unknown vendor-specific fields should be preservable through `raw` or `extra`
- `materials[].sceneRange.start/end` is optional; when absent, materials are laid out sequentially by array order
- Version 1 allows manual material timing only for non-overlapping placement on the main video track

## Normalization Rules

`ScenePayloadNormalizer` is responsible for turning flexible input into a stable internal structure.

It should:

- Generate `sceneId` and `materialId` when missing
- Normalize aliases such as:
  - `audio` to `audioUrl`
  - `subtitle` to `subtitles`
  - `voiceName` to `voice`
- Convert single objects to arrays for:
  - `materials`
  - `subtitles`
  - `wordArts`
- Merge style layers in this order:
  - global defaults
  - scene overrides
  - item overrides
- Fill default canvas and default layout values when not provided
- Preserve unknown pass-through fields in `raw` or `extra`

## Duration Resolution

`SceneDurationResolver` determines the duration of each scene before clip assembly.

Resolution priority:

1. explicit `sceneDuration`
2. explicit `dubbing.duration`
3. sum of material durations
4. fallback default duration such as `3.0`

Version 1 deliberately does not probe remote media length. Duration is resolved from request data only.

Practical rule set:

- If `sceneDuration` exists, use it
- Else if `dubbing.duration` exists, use it
- Else if the scene contains materials with explicit durations, use their sum
- Else if the scene contains dubbing but no explicit duration hint, use a safe fallback
- Else use the default scene duration

This keeps the template deterministic and free from network or metadata lookup dependencies.

Resolved scene duration is fixed once chosen. Version 1 does not auto-expand or auto-truncate a scene after resolution.

## Timeline Assembly

`SceneTimelineAssembler` converts normalized scenes into IMS timeline objects.

### Video

- Maintain a timeline cursor for the full video
- Each scene receives:
  - `sceneStart`
  - `sceneEnd`
- Each material becomes one `VideoTrackClip`
- Each material gets a stable clip identifier such as `scene-1-material-2`
- Materials are sequential by default inside each scene
- A material can optionally define `sceneRange.start/end` for explicit non-overlapping placement
- Layout defaults to full-canvas cover when not supplied

### Transitions

- Scene-level transitions are supported
- Material-level transitions are supported
- Default behavior uses scene-level transitions only
- Material-level transitions are opt-in
- Scene-level transitions should be attached to the scene entry clip
- Material-level transitions should be attached to the target material clip
- Transitions are treated as crossfades over existing clip durations and do not extend scene duration

### Dubbing

Each scene may produce one dubbing clip on the main narration track.

Rules:

- If `dubbing.audioUrl` exists, create a raw `AudioTrackClip`
- Else if `dubbing.text` and `dubbing.voice` exist, create an `AI_TTS` clip
- The dubbing clip covers the scene time range unless a later revision introduces partial narration support
- Global BGM remains separate and uses the existing `AudioBuilder::addBgm()` flow
- If a caller wants dubbing-driven scene length without `sceneDuration`, it must provide `dubbing.duration` in version 1
- `dubbing.duration` is used only for scene sizing when `sceneDuration` is absent; the assembled dubbing clip still spans the final resolved scene range

### Subtitles

Ordinary subtitles map to `SubtitleTrackClip`.

Rules:

- `start` and `end` are scene-relative
- They are translated to absolute timeline time during assembly
- `referenceMaterialId` is resolved to the generated material clip ID
- Subtitle style is derived from the merged subtitle style layers

### Word Art

Version 1 implements word art using a dedicated subtitle track with independent style presets.

Rules:

- `wordArts[]` is separate from ordinary subtitles
- Each item becomes a `SubtitleTrackClip`
- Word art uses `wordArtStyle` instead of ordinary subtitle defaults
- Individual items can override font, colors, outline, box, shadow, and layout

This keeps the public schema stable while leaving room for a future dedicated sticker or text-art representation.

### Global Effects

The new flow should continue reusing existing global effect helpers for:

- watermark
- background image if later needed
- global filter
- global VFX
- global BGM

## Track Layout

The assembled timeline should typically contain:

- one main video track for scene materials
- one main dubbing audio track
- zero or one BGM track
- one subtitle track for ordinary subtitles
- one subtitle track for word art
- zero or more effect tracks for watermark and global effects

This is the default single-main-track arrangement for version 1.

## Validation

Validation should be split into structure validation and composition validation.

### Structure Validation

Handled during normalization:

- `scenes` must exist and be non-empty
- each scene must contain at least one material
- each material must contain `type` and `url`
- `type` must be `video` or `image`
- numeric fields must not be negative
- subtitle and word art ranges must satisfy `start < end`
- if dubbing exists, it must provide `audioUrl` or `text + voice`
- referenced material IDs must exist inside the current scene

### Composition Validation

Handled during assembly:

- scene item timing must stay inside the resolved scene duration
- when `sceneDuration` is explicit, any material, subtitle, word art, or dubbing timing that exceeds it should fail validation instead of being truncated
- transition duration must not exceed the target clip duration
- resolved reference material timing should overlap the subtitle or word art timing when the reference is present
- sequential material placement should not overflow the scene duration without an explicit override strategy
- manually timed materials must not overlap in version 1

## Error Model

Errors should be machine-readable and include a payload path.

Recommended shape:

```php
array(
    'code' => 'INVALID_SCENE_SUBTITLE_RANGE',
    'message' => 'Subtitle end time must be greater than start time.',
    'path' => 'scenes[1].subtitles[2].end',
)
```

This lets front-end callers point directly to the broken editor field.

## Backward and Forward Compatibility

The scene schema is expected to evolve. To support that:

- normalization should absorb alias and shape differences
- the template should depend on normalized structures only
- `extra` and `raw` fields should be preserved for vendor-specific pass-through
- new scene item types can be added later without replacing the overall flow

## Extension Points

The design should leave room for future additions such as:

- `material.effects[]`
- `scene.effects[]`
- richer sticker elements
- audio ducking and voice mixing
- media metadata resolvers
- reusable style presets or themes
- more advanced scene layout strategies

## Testing Strategy

The implementation should be covered with focused tests for:

- payload normalization
- duration resolution
- scene-relative to timeline-relative time conversion
- subtitle reference resolution
- dubbing priority between `audioUrl` and `text + voice`
- scene-level and material-level transition mapping
- word art track generation
- validation failures with stable error paths

The existing smoke-test style in this repository is a good starting point, but the new feature should add targeted tests around the new orchestration classes.

## Acceptance Criteria

The design is considered satisfied when:

- a caller can submit one request with multiple scenes
- each scene can carry one or more materials
- each scene can define dubbing via `audioUrl` or `text + voice`
- each scene can define ordinary subtitles and word art independently
- scene-relative timing is automatically converted into absolute timeline timing
- scene-level and material-level transitions are both representable
- the result builds through the existing timeline and submission pipeline

## Recommendation

Proceed with a dedicated scene-based template implementation centered on normalized scene payloads. This offers the cleanest match to the editor UI, minimizes changes to current abstractions, and creates a stable foundation for later evolution.
