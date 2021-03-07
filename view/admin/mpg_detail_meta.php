<div class='form-field form-field-wide'>
<?php 
    $filtered = ["MerchantOrderNo", "MerchantID", "Amt"];
?>
    <div>
        <div>
            <b style="line-height: 36px;height: 36px;vertical-align: bottom;">藍新結帳資訊</b>
            <button type="button" id="mpg_metadata_details_toggle_btn" class="handlediv" style="display:inline-block;">
                <span class="screen-reader-text">切換面板: 訂單備註</span>
                <span class="toggle-indicator" aria-hidden="true"></span>
            </button>
        </div>
        <p class="merchant_trade_no_row">廠商訂單編號 MerchantOrderNo: <?php echo (isset($metadata['MerchantOrderNo']) ? $metadata['MerchantOrderNo'] : '');?></p>
    </div>
    <div id="mpg_metadata_details_for_dev" style="display:none;">
<?php
    foreach($metadata as $key => $value) {
        if (!in_array($key, $filtered)){
            echo "<div>$key: $value</div>";
        }
    }
?>
    <p>回傳資訊：</p>
<?php
    foreach($responsed_metadata as $key => $value) {
        if (!in_array($key, $filtered)){
            echo "<div>$key: $value</div>";
        }
    }
?>   
    </div>
    <script>
        var mpgBtn = document.getElementById('mpg_metadata_details_toggle_btn')
        mpgBtn.addEventListener('click', function(){
            var mpgDiv = document.getElementById('mpg_metadata_details_for_dev')
            if (mpgBtn.getAttribute('aria-expanded') == "true"){
                mpgDiv.style.display = "none"
            } else {
                mpgDiv.style.display = "block"
            }
        })
    </script>
</div>
<style>
.merchant_trade_no_row {
    margin-top: 0; 
    margin-bottom: 8px; 
    font-size:14px;
    font-weight: 800;
}
</style>