<?php
/**
 * Customer-facing tip control (cart + checkout).
 *
 * Rendered by {@see \Tipping\Frontend\TipControl::render()}. All dynamic values
 * are escaped here; the controller passes already-validated data.
 *
 * @package Tipping
 *
 * @var \Tipping\Settings\Options $options
 * @var list<float>              $presets
 * @var array{mode: string, preset: int, amount: float} $current
 * @var bool                     $isPercent
 * @var bool                     $allowCustom
 * @var string                   $label
 * @var string                   $description
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Tipping\Frontend\TipControl;

$tipping_group_name = 'tipping_choice';
?>
<div class="tipping" role="group" aria-labelledby="tipping-label">
    <p class="tipping__label" id="tipping-label"><?php echo esc_html($label); ?></p>

    <?php if ('' !== $description) : ?>
        <p class="tipping__description"><?php echo esc_html($description); ?></p>
    <?php endif; ?>

    <div class="tipping__options">
        <button
            type="button"
            class="tipping__option<?php echo 'none' === $current['mode'] ? ' is-active' : ''; ?>"
            data-tipping-mode="none"
            aria-pressed="<?php echo 'none' === $current['mode'] ? 'true' : 'false'; ?>"
        >
            <?php esc_html_e('No tip', 'tipping'); ?>
        </button>

        <?php foreach ($presets as $tipping_index => $tipping_value) : ?>
            <?php
            $tipping_is_active = 'preset' === $current['mode'] && (int) $current['preset'] === (int) $tipping_index;
            ?>
            <button
                type="button"
                class="tipping__option<?php echo $tipping_is_active ? ' is-active' : ''; ?>"
                data-tipping-mode="preset"
                data-tipping-preset="<?php echo esc_attr((string) (int) $tipping_index); ?>"
                aria-pressed="<?php echo $tipping_is_active ? 'true' : 'false'; ?>"
            >
                <?php echo esc_html(TipControl::formatPreset((float) $tipping_value, (bool) $isPercent)); ?>
            </button>
        <?php endforeach; ?>

        <?php if ($allowCustom) : ?>
            <button
                type="button"
                class="tipping__option tipping__option--custom<?php echo 'custom' === $current['mode'] ? ' is-active' : ''; ?>"
                data-tipping-mode="custom-toggle"
                aria-pressed="<?php echo 'custom' === $current['mode'] ? 'true' : 'false'; ?>"
                aria-expanded="<?php echo 'custom' === $current['mode'] ? 'true' : 'false'; ?>"
                aria-controls="tipping-custom-field"
            >
                <?php esc_html_e('Custom', 'tipping'); ?>
            </button>
        <?php endif; ?>
    </div>

    <?php if ($allowCustom) : ?>
        <div
            class="tipping__custom"
            id="tipping-custom-field"
            <?php echo 'custom' === $current['mode'] ? '' : 'hidden'; ?>
        >
            <label class="tipping__custom-label" for="tipping-custom-amount">
                <?php esc_html_e('Enter a custom amount', 'tipping'); ?>
            </label>
            <input
                type="number"
                inputmode="decimal"
                min="0"
                step="0.01"
                id="tipping-custom-amount"
                class="tipping__custom-input"
                value="<?php echo 'custom' === $current['mode'] && $current['amount'] > 0 ? esc_attr((string) $current['amount']) : ''; ?>"
                placeholder="0.00"
            />
        </div>
    <?php endif; ?>

    <p class="tipping__status" aria-live="polite"></p>
</div>
<?php
unset($tipping_group_name);
