<?php
/**
 * Email Details template
 *
 * @var array $details List of details
 */

if (!is_array($details) || empty($details)) {
    return;
}
?>
<table width="100%" style="margin-bottom: 20px; border-collapse: separate; border-spacing: 0;">
    <?php foreach ($details as $label => $value) : ?>
    <tr>
        <td style="padding: 10px; background-color: #f9f9f9; border: 1px solid #e0e0e0; width:20%">
            <strong> <?php echo esc_html($label) ?></strong>
        </td>
        <td style="padding: 10px; background-color: #f9f9f9; border: 1px solid #e0e0e0; width:80%">
            <?php echo esc_html($value) ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
