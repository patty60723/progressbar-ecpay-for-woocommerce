<?php
$raw_data = file_get_contents('PHP://input');
$raw_data = urldecode($raw_data);
parse_str($raw_data, $data);
$referer = $data['_wp_http_referer'] ?? false;
if ($referer) {
    $url = parse_url($referer);
    $query_string = $url['query'] ?? '';
    parse_str($query_string, $payload);

    if (count($payload)) {
        if ($this->validation_map_keys($payload)) {
            foreach ($payload as $key => $value) {
                echo "<input type='hidden' name='$key' value='$value'>";
            }
            $info = '當前選擇的超商為: ' . $this->logisticsSubTypes[$payload['LogisticsSubType']] . ' ' . $payload['CVSStoreName'] . PHP_EOL;
            $info .= '地址: ' . $payload['CVSAddress'] . PHP_EOL;
            $info .= '電話: ' . $payload['CVSTelephone'] . PHP_EOL;
            echo wpautop($info);
        } else {
            echo '發生錯誤，請重新選取超商!';
        }
    }

}

$availableLogisticsSubTypes = $this->get_option('availableLogisticsSubTypes');

?>

<?php if (is_array($availableLogisticsSubTypes) && count($availableLogisticsSubTypes)) { ?>
    <form
            id='pb_ec_map_form'
            method='post'
            action='<?php echo $mapUrl; ?>'>
        <select id="logistics-sub-types-select">
            <?php foreach ($availableLogisticsSubTypes as $type) { ?>
                <option <?php echo $type === @$payload['LogisticsSubType'] ? 'selected' : ''; ?>
                        value="<?php echo $type; ?>"><?php echo $this->logisticsSubTypes[$type]; ?></option>
            <?php } ?>
        </select>
        <?php
        foreach ($metadata as $key => $value) {
            echo "<input type='hidden' name='$key' value='$value'>";
        }
        ?>
        <button type="submit" class="button alt">瀏覽地圖</button>
    </form>

    <script>
        var logisticsSubTypesSelect = document.getElementById('logistics-sub-types-select');

        logisticsSubTypesSelect.addEventListener('change', e => {
            document.querySelector('#pb_ec_map_form > [name=LogisticsSubType]').value = e.target.value;
        });
    </script>

<?php } else { ?>

    目前沒有支援的超商

<?php } ?>
