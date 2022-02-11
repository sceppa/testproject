<?php
/* Variables:
 * 	• $ui_js_url -- URL of ui.js module
 * 	• $framework_js_url -- URL of framework.js module
 * 	• $tree_js -- string with JS representation of the nested tree to show on page
 */
?>
<script type="module">
	import { ui } from '<?php echo esc_url( $ui_js_url ); ?>';
	import { subscribe, updateTree, updateSecretURL, updateResponseMessage } from '<?php echo esc_url( $framework_js_url ); ?>';
	const tree = <?php echo $tree_js; ?>;
	const url = <?php echo $secret_url ? "'" . $secret_url . "'" : 'null'; ?>;
	const responseMessage = <?php echo $response_message ? "'" . $response_message . "'" : "'';"; ?>;
	subscribe( ( newTree, newURL, newExpanded ) => ui( newTree, newURL, newExpanded, document.getElementById( 'ui' ) ) );
	updateTree( tree );
	updateSecretURL( url );
	updateResponseMessage( responseMessage );
</script>
