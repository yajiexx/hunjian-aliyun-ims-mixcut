# Scene Mixcut Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a reusable scene-based mixcut flow that accepts `scenes[]` payloads, builds IMS timelines automatically, and exposes a new service entrypoint.

**Architecture:** Introduce a small scene orchestration layer with three focused responsibilities: payload normalization, duration resolution, and timeline assembly. Keep the public output compatible with the existing template flow so `MediaProducingService` can submit scene mixcut jobs through the current client without changing lower-level IMS adapters.

**Tech Stack:** PHP 7.3, existing timeline models/builders, local smoke-test runner in `tests/run.php`

---

### Task 1: Add Failing Tests For Scene Mixcut Template

**Files:**
- Modify: `C:\xxm_project\2026\hunjian\demo2\tests\SmokeTest.php`
- Test: `C:\xxm_project\2026\hunjian\demo2\tests\run.php`

- [ ] **Step 1: Write the failing tests**

Add tests that cover:
- a multi-scene payload building video, audio, subtitle, and word art tracks
- `audioUrl` taking precedence over `text + voice`
- explicit validation failure with a stable path when subtitle timing exceeds `sceneDuration`
- service-level submission through a new `submitSceneMixcut()` method

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests\run.php`
Expected: FAIL because the new scene mixcut classes and service entrypoint do not exist yet.

### Task 2: Add Scene Mixcut Validation and Normalization

**Files:**
- Create: `C:\xxm_project\2026\hunjian\demo2\src\Exception\InvalidSceneMixcutException.php`
- Create: `C:\xxm_project\2026\hunjian\demo2\src\Scene\ScenePayloadNormalizer.php`
- Test: `C:\xxm_project\2026\hunjian\demo2\tests\SmokeTest.php`

- [ ] **Step 1: Write the minimal implementation**

Implement:
- machine-readable exception fields for error code and path
- payload normalization for scene IDs, material IDs, arrays, defaults, alias fields, and style merging
- structural validation for required scene, material, dubbing, and timing fields

- [ ] **Step 2: Run test to verify the normalization tests pass**

Run: `php tests\run.php`
Expected: Earlier missing-class failures should move forward; validation-related assertions should now pass or fail for the next missing layer.

### Task 3: Add Duration Resolution and Timeline Assembly

**Files:**
- Create: `C:\xxm_project\2026\hunjian\demo2\src\Scene\SceneDurationResolver.php`
- Create: `C:\xxm_project\2026\hunjian\demo2\src\Scene\SceneTimelineAssembler.php`
- Test: `C:\xxm_project\2026\hunjian\demo2\tests\SmokeTest.php`

- [ ] **Step 1: Write the minimal implementation**

Implement:
- resolved scene duration rules using `sceneDuration`, `dubbing.duration`, material duration sums, and fallback default
- absolute timeline cursor handling
- one main video track from ordered material clips
- one narration audio track
- one ordinary subtitle track and one word art subtitle track
- transition precedence and reference material ID mapping
- composition validation for overflow and overlapping manually timed materials

- [ ] **Step 2: Run test to verify scene assembly passes**

Run: `php tests\run.php`
Expected: Track-shape assertions should pass, with any remaining failures isolated to template/service integration.

### Task 4: Add Template and Service Entry Point

**Files:**
- Create: `C:\xxm_project\2026\hunjian\demo2\src\Template\SceneMixcutTemplate.php`
- Modify: `C:\xxm_project\2026\hunjian\demo2\src\Service\MediaProducingService.php`
- Test: `C:\xxm_project\2026\hunjian\demo2\tests\SmokeTest.php`

- [ ] **Step 1: Write the minimal implementation**

Implement:
- `SceneMixcutTemplate::build(array $context = array())`
- output config handling consistent with existing templates
- `MediaProducingService::submitSceneMixcut(array $context, array $options = array())`

- [ ] **Step 2: Run test to verify all scene mixcut tests pass**

Run: `php tests\run.php`
Expected: PASS for the new scene mixcut tests and all existing smoke tests.

### Task 5: Final Verification And Usage Notes

**Files:**
- Optional Modify: `C:\xxm_project\2026\hunjian\demo2\README.md`
- Test: `C:\xxm_project\2026\hunjian\demo2\tests\run.php`

- [ ] **Step 1: Add a small usage note if the public API needs documentation**

Only add this if the new entrypoint would otherwise be hard to discover.

- [ ] **Step 2: Run full verification**

Run: `php tests\run.php`
Expected: PASS with zero failing smoke tests.

- [ ] **Step 3: Review git diff**

Run: `git status --short`
Expected: only scene mixcut implementation files plus any intentional documentation updates; preserve unrelated `composer.json` changes untouched.
