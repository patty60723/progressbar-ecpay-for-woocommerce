<div class='form-field form-field-wide'>
<?php 
    $filtered = ["MerchantTradeNo", "MerchantID", "TotalAmount", "IgnorePayment", "CheckMacValue", "EncryptType"];
?>
    <div>
        <div>
            <b style="line-height: 36px;height: 36px;vertical-align: bottom;">綠界結帳資訊</b>
            <button type="button" id="ecpay_metadata_details_toggle_btn" class="handlediv" style="display:inline-block;">
                <span class="screen-reader-text">切換面板: 訂單備註</span>
                <span class="toggle-indicator" aria-hidden="true"></span>
            </button>
        </div>
        <p class="merchant_trade_no_row">廠商訂單編號 MerchantTradeNo: <?php echo $metadata['MerchantTradeNo'];?></p>
    </div>
    <div id="ecpay_metadata_details_for_dev" style="display:none;">
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
        var btn = document.getElementById('ecpay_metadata_details_toggle_btn')
        btn.addEventListener('click', function(){
            var div = document.getElementById('ecpay_metadata_details_for_dev')
            if (btn.getAttribute('aria-expanded') == "true"){
                div.style.display = "none"
            } else {
                div.style.display = "block"
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