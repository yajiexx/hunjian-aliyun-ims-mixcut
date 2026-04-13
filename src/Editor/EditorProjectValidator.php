<?php

namespace Hunjian\AliyunImsMixcut\Editor;

use Hunjian\AliyunImsMixcut\Exception\InvalidEditorProjectException;

/**
 * Validate normalized editor projects before timeline compilation.
 */
class EditorProjectValidator
{
    /**
     * Validate normalized project payload.
     *
     * @param array $project
     *
     * @return void
     */
    public function validate(array $project)
    {
        $duration = $project['sequence']['duration'];

        foreach ($project['sequence']['layers'] as $layerIndex => $layer) {
            foreach ($layer['items'] as $itemIndex => $item) {
                $itemPath = 'sequence.layers[' . $layerIndex . '].items[' . $itemIndex . ']';
                $this->validateRequiredFields($layer['type'], $item, $itemPath);

                $clipEnd = $item['start'] + $item['duration'];
                if ($clipEnd > ($duration + 0.00001)) {
                    $this->fail(
                        'INVALID_EDITOR_CLIP_RANGE',
                        'Clip timing must stay inside sequence duration.',
                        $itemPath . '.duration'
                    );
                }

                if ($item['sourceRange'] !== null) {
                    $sourceDuration = $item['sourceRange']['out'] - $item['sourceRange']['in'];
                    if ($sourceDuration + 0.00001 < $item['duration']) {
                        $this->fail(
                            'INVALID_EDITOR_SOURCE_RANGE',
                            'Source range must cover the clip duration.',
                            $itemPath . '.sourceRange.out'
                        );
                    }
                }

                if ($item['transition'] !== null && $item['transition']['duration'] > $item['duration']) {
                    $this->fail(
                        'INVALID_EDITOR_TRANSITION',
                        'Transition duration must not exceed clip duration.',
                        $itemPath . '.transition.duration'
                    );
                }
            }
        }
    }

    /**
     * Validate layer-specific required fields.
     *
     * @param string $layerType
     * @param array $item
     * @param string $path
     *
     * @return void
     */
    protected function validateRequiredFields($layerType, array $item, $path)
    {
        if ($layerType === 'text') {
            if ($item['text'] === null || $item['text'] === '') {
                $this->fail('INVALID_EDITOR_TEXT_CONTENT', 'Text items must provide text.', $path . '.text');
            }

            return;
        }

        if ($layerType === 'background' && $item['type'] === 'color') {
            if ($item['color'] === null || $item['color'] === '') {
                $this->fail('INVALID_EDITOR_BACKGROUND', 'Color backgrounds must provide a color value.', $path . '.color');
            }

            return;
        }

        if ($item['url'] === null || $item['url'] === '') {
            $this->fail('INVALID_EDITOR_MEDIA_URL', 'Media items must provide a url.', $path . '.url');
        }
    }

    /**
     * Throw a validation exception.
     *
     * @param string $code
     * @param string $message
     * @param string|null $path
     *
     * @return void
     */
    protected function fail($code, $message, $path = null)
    {
        throw new InvalidEditorProjectException($code, $message, $path);
    }
}
