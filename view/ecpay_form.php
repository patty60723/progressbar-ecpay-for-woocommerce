<form 
    id='pb_ec_form' 
    method='post' 
    action='<?php echo $aioUrl; ?>'>
    <?php 
        foreach($metadata as $key => $value) {
            echo "<input type='hidden' name='$key' value='$value'>";
        }
    ?>
</form>

<script>
    setTimeout(function(){
        document.getElementById('pb_ec_form').submit();
    }, 500);
</script>