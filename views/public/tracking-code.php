<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo html_escape($id); ?>"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){ dataLayer.push(arguments); }
	gtag('js', new Date());
	gtag('config', '<?php echo html_escape($id); ?>');
</script>
