<script type="text/javascript">
{literal}
Ext.regController('search', {

	/**
	 * Sets the active view
	 */
	deeplink: function() {
		var viewport = Ext.getCmp('viewport');
		viewport.setActiveItem(1);
	}
});
{/literal}
</script>