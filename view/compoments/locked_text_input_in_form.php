<tr valign="top">
    <th scope="row" class="titledesc">
        <label>
            <?php echo (isset($value["title"]) ? $value["title"] : "") ?>
        </label>
    </th>
    <td class="forminp">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php echo (isset($value["title"]) ? $value["title"] : "") ?></span>
            </legend>
            <input 
                class="input-text regular-input <?php echo (isset($value["class"]) ? $value["class"] : "") ?> ?>" 
                type="text"  
                style="<?php echo (isset($value["style"]) ? $value["style"] : "") ?>" 
                value="<?php echo (isset($value["value"]) ? $value["value"] : "") ?>"
                placeholder="<?php echo (isset($value["placeholder"]) ? $value["placeholder"] : "") ?>" 
                disabled />
        </fieldset>
    </td>
</tr>