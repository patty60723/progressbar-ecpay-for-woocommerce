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
            <?php 
                foreach($value["options"] as $optionValue => $optionName) {
                    $checked = (is_array($optionValues) && in_array($optionValue, $optionValues)) ? "checked" : "";
                    echo "<div><label><input type='checkbox' name='{$inputName}[]' value='{$optionValue}' {$checked} />$optionName</label></div>";
                }
            ?>
        </fieldset>
    </td>
</tr>