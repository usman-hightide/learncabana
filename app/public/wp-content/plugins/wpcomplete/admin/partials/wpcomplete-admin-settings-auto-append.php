  
  </td>
</table>
<table style="margin-top: -20px;">
  <tbody>
    <tr>
      <td>
    <div class="wpcomplete-auto-attend-container">
      <input type="hidden" name="<?php echo $this->plugin_name . '_auto_append'; ?>" value="false">
      <label>
        <input type="checkbox" name="<?php echo $this->plugin_name . '_auto_append'; ?>" id="<?php echo $this->plugin_name . '_auto_append'; ?>" value="true" <?php checked( 'true', $is_enabled ); ?>> 
        <?php echo __("Automatically add complete button to enabled posts &amp; pages for me.", $this->plugin_name); ?>
      </label>
    </div>
