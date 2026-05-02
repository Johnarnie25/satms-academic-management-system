<?php
// Small snippet to restore dark mode before CSS loads.
?>
<script>
try{
    if (typeof localStorage !== 'undefined' && localStorage.getItem && localStorage.getItem('stms_dark_mode') === '1'){
        document.documentElement.classList.add('dark');
        document.body.classList.add('dark');
    }
}catch(e){}
</script>
