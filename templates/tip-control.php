<?php
/**
 * Customer-facing tip control (checkout).
 *
 * Rendered by {@see \Tipping\Frontend\TipControl::render()}. All dynamic values
 * are escaped here; the controller passes already-validated data.
 *
 * @package Tipping
 *
 * @var \Tipping\Settings\Options $options
 * @var list<float>              $presets
 * @var array{mode: string, preset: int, recipient: string} $current
 * @var list<array{id: string, label: string}> $recipients
 * @var bool                     $isPercent
 * @var string                   $label
 * @var string                   $description
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Tipping\Frontend\TipControl;
?>
<div class="tipping" role="group" aria-labelledby="tipping-label">
    <p class="tipping__label" id="tipping-label"><?php echo esc_html($label); ?></p>

    <?php if ('' !== $description) : ?>
        <p class="tipping__description"><?php echo esc_html($description); ?></p>
    <?php endif; ?>

    <?php
    $context = compact('options', 'presets', 'current', 'recipients', 'isPercent', 'label', 'description');
    /**
     * Fires before the preset buttons inside the tip control.
     *
     * PRO add-ons use this to render recipient or cause selectors.
     *
     * @param array<string, mixed> $context Template context passed from TipControl.
     */
    do_action('tipping/control_before_options', $context);
    ?>

    <div class="tipping__options">
        <button
            type="button"
            class="tipping__option tipping__option--none<?php echo 'none' === $current['mode'] ? ' is-active' : ''; ?>"
            data-tipping-mode="none"
            aria-pressed="<?php echo 'none' === $current['mode'] ? 'true' : 'false'; ?>"
        >
            <?php esc_html_e('No tip', 'plogins-tipping'); ?>
        </button>

        <?php foreach ($presets as $tipping_index => $tipping_value) : ?>
            <?php
            $tipping_is_active = 'preset' === $current['mode'] && (int) $current['preset'] === (int) $tipping_index;
            ?>
            <button
                type="button"
                class="tipping__option tipping__option--amount<?php echo $tipping_is_active ? ' is-active' : ''; ?>"
                data-tipping-mode="preset"
                data-tipping-preset="<?php echo esc_attr((string) (int) $tipping_index); ?>"
                aria-pressed="<?php echo $tipping_is_active ? 'true' : 'false'; ?>"
            >
                <?php echo esc_html(TipControl::formatPreset((float) $tipping_value, (bool) $isPercent)); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <p class="tipping__status" aria-live="polite"></p>
</div>
