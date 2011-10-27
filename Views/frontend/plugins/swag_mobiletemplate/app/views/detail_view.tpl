<script type="text/javascript">
/**
 * @file detail.js
 * @link http://www.shopware.de
 * @author S.Pohl <stp@shopware.de>
 * @date 11-05-11
 */

Ext.ns('App.views.Viewport', 'App.views.Shop', 'App.views.Search', 'App.views.Cart', 'App.views.Account', 'App.views.Info', 'App.views.Checkout');

/**
 * Main Detail Panel
 *
 * Contains the three different sections (e.g. Detail, Comments, Pictures)
 *
 * @access public
 * @class
 * @extends Ext.Panel
 */
App.views.Shop.detail = Ext.extend(Ext.Panel,
/** @lends App.views.Shop.detail# */
{
	id: 'detail',
	layout: 'card',
	listeners: {
		scope: this,

		/**
		 * Event listener
		 *
		 * @param me
		 */
		beforeactivate: function(me) {
			var shopView   = Ext.getCmp('shop'),
				artListing = Ext.getCmp('artListing');

			if(Ext.getCmp('filterBtn')) {
				Ext.getCmp('filterBtn').hide();
				shopView.toolBar.doLayout();
			}
			shopView.backBtn.setHandler(me.onBackBtn);

		},

		/**
		 * Event listener
		 *
		 * @param me
		 */
		beforedeactivate: function(me) {
			var shopView   = Ext.getCmp('shop');
			shopView.backBtn.setHandler(shopView.onBackBtn, shopView);

			/** ... only delete the navigation buttons if they exists */
			if(me.navBtn) {
				me.navBtn.destroy();
			}
		},

		/**
		 * deactivate
		 *
		 * Event listener
		 *
		 * @param me
		 */
		deactivate: function(me) {
			var shopView   = Ext.getCmp('shop'),
				artListing = Ext.getCmp('artListing');

			if(Ext.getCmp('filterBtn')) {
				Ext.getCmp('filterBtn').show();
				shopView.toolBar.doLayout();
			}
			me.destroy();
		}
	},

	/**
	 * Creates the needed sub components
	 */
	initComponent: function() {
		var me = this, shop = Ext.getCmp('shop');

		/* Navigationsbutton */
		me.navBtn = new Ext.SegmentedButton({
			id: 'detailSegBtn',
			allowMultiple: false,
			ui: 'light',
			items: [
				{ text: '{s name="MobileDetailSplitButtonDetail"}Detail{/s}', pressed: true },
				{ text: '{s name="MobileDetailSplitButtonComments"}Kommentare{/s}' },
				{ text: '{s name="MobileDetailSplitButtonPictures"}Bilder{/s}' }
			],
			listeners: {
				scope: this,
				toggle: me.onNavBtn
			}
		});

		shop.toolBar.setTitle('');
		shop.toolBar.add([me.navBtn]);
		shop.toolBar.doComponentLayout();

		App.views.Shop.detail.superclass.initComponent.call(me);
	},


	/**
	 * Handles the back button
	 */
	onBackBtn: function() {
		var store = App.stores.Detail;
		store.clearListeners();
		var tmpRec = store.getAt(0);

		Ext.dispatch({
			controller: 'category',
			action: 'show',
			categoryID: tmpRec.data.categoryID,
			store: App.stores.Listing,
			type: 'slide',
			direction: 'right'
		});
	},

	/**
	 * Handles the segmented button to change the section
	 *
	 * @param pnl
	 * @param btn
	 * @param pressed
	 */
	onNavBtn: function(pnl, btn, pressed) {
		Ext.dispatch({
			controller: 'detail',
			action: 'handleNavigationButton',
			button: btn,
			pressed: pressed
		});
		return true;
	}
});

/**
 * Liveshopping interval
 * @private
 */
var interval;

/**
 * Detail view
 *
 * Contains the basic article informations
 *
 * @access public
 * @class
 * @extends Ext.Panel
 */
App.views.Shop.info = Ext.extend(Ext.Panel,
/** @lends App.views.Shop.info# */
{
	id: 'teaser',
	layout: 'vbox',
	scroll: 'vertical',
	flex: 1,
	autoHeight: true,
	listeners: {
		scope: this,
		beforeactivate: function(me) {
			me.setLoading(true);
		},
		activate: function(me) {
			me.info.refresh();
			me.desc.refresh();
			me.bundle.refresh();
			me.doLayout();
			me.formPnl.doLayout();
			me.setLoading(false);
		},
		deactivate: function(me) {
			App.stores.Detail.clearListeners();
			clearInterval(interval);
			me.destroy();
		}
	},

	initComponent: function() {
		var me = this, store = App.stores.Detail, tpl = App.views.Shop;

		/** Teaser Panel with main picture */
		me.info = new Ext.DataView({
			store: store,
			tpl: tpl.detailTpl,
			scroll: false,
			autoWidth: true,
			autoHeight: true,
			style: 'width: 100%',
			itemSelector: '.image',
			listeners: {
				scope: this,
				el: {
					tap: me.onImageTap,
					delegate: '.image'
				}
			}
		});

		store.on({
			scope: this,
			storeLoaded: me.onStoreLoaded
		});

		/** Amount spinner */
		me.spinner = new Ext.form.Spinner({
			value: 1,
			label: '{s name="MobileDetailAmountLabel"}Anzahl{/s}',
			required: true,
			xtype: 'spinnerfield',
			minValue: 1,
			maxValue: 100,
			width: '100%',
			cycle: false,
			name: 'sQuantity'
		});

		/** "Buy now" form panel */
		me.formPnl = new Ext.form.FormPanel({
			width: '100%',
			items: [
				{
					id: 'buyFieldset',
					xtype: 'fieldset',
					defaults: {
						labelWidth: '50%'
					},
					items: [me.spinner]
				}
			]
		});

		/** "Buy now"-Button */
		me.buyBtn = new Ext.Button({
			id: 'buyBtn',
			ui: 'confirm round',
			text: '{s name="MobileDetailBuyButton"}In den Warenkorb legen{/s}',
			scope: this,
			handler: me.onBuyBtn,
			height: '33px'
		});

		/** Bundle support */
		me.bundle = new Ext.DataView({
			store: store,
			tpl: Ext.XTemplate.from('ShopbundleTpl'),
			itemSelector: '#bundleBtn',
			scroll: false,
			width: '100%',
			autoHeight: '100%',
			style: 'margin-top: 1em',
			listeners: {
				scope: this,
				el: {
					delegate: '#bundleBtn',
					tap: me.onBundleBtn
				}
			}
		});

		/** Article description */

		me.desc = new Ext.DataView({
			store: store,
			style: 'width: 100%',
			tpl: Ext.XTemplate.from('Shopdesctpl'),
			scroll: false,
			autoWidth: true,
			itemSelector: '.outer-desc'
		});

		Ext.apply(me, {
			items: [
				me.info,
				me.formPnl,
				me.buyBtn
			]
		});

		App.views.Shop.info.superclass.initComponent.call(this);
	},

	/**
	 * Handles the different article types and creates the needed elements (e.g. variants, configurator, bundles)
	 */
	onStoreLoaded: function() {
		Ext.dispatch({
			controller: 'detail',
			action: 'storeLoaded',
			view: this
		});

		return this;
	},

	/**
	 * Adds bundle articles to cart
	 */
	onBundleBtn: function() {
		var store = App.stores.Detail,
			item = store.getAt(0),
			bundle = item.data.sBundles;

		App.stores.Cart.addBundle(item.data.ordernumber, bundle[0].id);
	},

	/**
	 * Adds an article to cart
	 */
	onBuyBtn: function() {
		var values = this.formPnl.getValues();
		App.stores.Cart.add(values);
		Ext.Msg.alert('{s name="MobileDetailCart"}Warenkorb{/s}', '{s name="MobileDetailArticleInCart"}Der Artikel wurde erfolgreich in den Warenkorb gelegt.{/s}', Ext.emptyFn);
	},

	/**
	 * Calls an controller action
	 */
	onImageTap: function() {
		Ext.dispatch({
			controller: 'detail',
			action: 'showPictures'
		})
	},

	/**
	 * Builds the needed form field for variant articles
	 *
	 * @param item - Store article data
	 */
	buildVariantField: function(item) {
		var me = this;
		var options = [];

		/* Main variant */
		options.push({
			text: item.data.additionaltext,
			value: item.data.ordernumber
		});

		for(var idx in item.data.sVariants) {
			var varArticle = item.data.sVariants[idx];
			options.push({
				text: varArticle.additionaltext,
				value: varArticle.ordernumber
			});
		}
		me.variant = new Ext.form.localeSelect({
			label: '{s name="MobileDetailSelectVariantLabel"}Bitte w&auml;hlen{/s}',
			required: true,
			options: options,
			name: 'sAdd',
			listeners: {
				scope: me,
				change: me.onVariantChange
			}
		});
		Ext.getCmp('buyFieldset').add(me.variant);
	},

	/**
	 * Builds the needed form elements for configurator articles
	 *
	 * @param rec - Store article data
	 */
	buildConfigurator: function(rec) {
		var me = this, groupIdx = 1, configurator = rec.data.sConfigurator, options = [];


		Ext.each(configurator, function(group) {

			var selected;

			/* Collection options */
			for(var idx in group.values) {
				var item =  group.values[idx];
				idx = ~~idx;

				if(group.selected_value == idx) {
					selected = item.optionname
				}
			
				options.push({
					text: item.optionname,
					value: item.optionID
				});
			}

			var selectBox = new Ext.form.localeSelect({
				options: options,
				name: 'group-'+groupIdx,
				listeners: {
					scope: me,
					change: me.onConfiguratorChange
				}
			});
			selectBox.setValue(selected);

			var fieldset = new Ext.form.FieldSet({
				cls: 'configuratorFieldset',
				title: group.groupname,
				instructions: group.groupdescription,
				items: [ selectBox ]
			});
			me.formPnl.add(fieldset);
			groupIdx++;
			options = [];
		});
	},

	/**
	 * Creates an hidden input field - needed
	 *
	 * @param item
	 */
	buildOrdernumber: function(item) {
		this.hiddenOrdernumber = new Ext.form.Hidden({
			id: 'hiddenOrdernumber',
			name: 'sOrdernumber',
			value: item.data.ordernumber
		});
		this.formPnl.add(this.hiddenOrdernumber);
	},

	/**
	 * Handles the configuration of an configurator article
	 *
	 * @param select
	 * @param val
	 */
	onConfiguratorChange: function(select, val) {
		Ext.dispatch({
			controller: 'detail',
			action: 'changeConfigurator',
			select: select,
			value: val,
			view: this
		});

		return true;
	},

	onVariantChange: function(select, value) {
		Ext.dispatch({
			controller: 'detail',
			action: 'changeVariants',
			select: select,
			value: value,
			view: this
		});

		return true;
	}
});

/**
 * Comments Main view
 *
 * Contains the different elements/views for the article comments
 *
 * @access public
 * @class
 * @extends Ext.Panel
 */
App.views.Shop.comments = Ext.extend(Ext.Panel,
/** @lends App.views.Shop.comments# */
{
	id: 'votes',
	layout: 'vbox',
	scroll: 'vertical',

	listeners: {
		scope: this,
		beforeactivate: function(me) {
			me.setLoading(true);
		},
		activate: function(me) {
			me.setLoading(false);
		},
		deactivate: function(me) {
			me.destroy();
		}
	},

	initComponent: function() {
		Ext.apply(this, {
			items: [
				new App.views.Shop.commentsView,
				new App.views.Shop.commentForm
			]
		});

		App.views.Shop.comments.superclass.initComponent.call(this);
	}
});

/**
 * Comments View
 *
 * Lists the user comments for an specific article
 *
 * @access public
 * @class
 * @extends Ext.DataView
 */
App.views.Shop.commentsView = Ext.extend(Ext.DataView,
/** @lends App.views.Shop.commentsView# */
{
	id: 'commentsView',
	store: App.stores.Detail,
	scroll: false,
	height: '100%',
	tpl: Ext.XTemplate.from('Shopcommenttpl'),
	itemSelector: '.headline',
	initComponent:  function() {
		var me = this;

		me.store.on({
			datachanged: me.onDataChanged,
			scope: this
		});
		me.update(me.store);

		App.views.Shop.commentsView.superclass.initComponent.call(me);
	},

	/**
	 * Updates store and refresh the layout
	 */
	onDataChanged: function() {
		this.update(this.store);
		this.refresh();
	},

	/**
	 * Pre dispatch the default update function
	 *
	 * @param store
	 */
	update: function(store) {
		if (store) {
			var item = store.getAt(0);
			if (item.data.sVoteComments.length > 0) {
				this.tpl = Ext.XTemplate.from('Shopcommenttpl');
			} else {
				this.tpl = Ext.XTemplate.from('Shopemptycommenttpl');
			}
		}
		App.views.Shop.commentsView.superclass.update.apply(this, arguments);
	}
});

/**
 * Comment Form
 *
 * Allows the user to create a comment for a specific article
 *
 * @access public
 * @class
 * @extends Ext.form.FormPanel
 */
App.views.Shop.commentForm = Ext.extend(Ext.form.FormPanel,
/** @lends App.views.Shop.commentForm# */
{
	id: 'commentForm',
	width: '100%',
	items: [{
		xtype: 'fieldset',
		title: '{s name="MobileDetailCommentTitle"}Kommentar abgeben{/s}',
		defaults: { labelWidth: '40%' },
		items: [
			{
				xtype: 'textfield',
				label: '{s name="MobileDetailCommentNameLabel"}Name{/s}',
				required: true,
				placeHolder: '{s name="MobileDetailCommentNamePlaceholder"}Max Mustermann{/s}',
				name: 'sVoteName'
			}, {
				xtype: 'emailfield',
				label: '{s name="MobileDetailCommentMailLabel"}E-Mail{/s}',
				required: true,
				placeHolder: '{s name="MobileDetailCommentMailPlaceholder"}me@shopware.ag{/s}',
				name: 'sVoteMail'
			}, {
				xtype: 'textfield',
				label: '{s name="MobileDetailCommentSummaryTitle"}Titel{/s}',
				required: true,
				placeHolder: '{s name="MobileDetailCommentSummaryPlaceholder"}Sch&ouml;nes Produkt{/s}',
				name: 'sVoteSummary'
			}, {
				xtype: 'localeSelectfield',
				label: '{s name="MobileDetailCommentRateTitle"}Bewertung{/s}',
				required: true,
				name: 'sVoteStars',
				options: [
					{ text: '{s name="MobileDetailCommentRate10"}10 sehr gut{/s}', value: '10' },
					{ text: '{s name="MobileDetailCommentRate9"}9{/s}', value: '9' },
					{ text: '{s name="MobileDetailCommentRate8"}8{/s}', value: '8' },
					{ text: '{s name="MobileDetailCommentRate7"}7{/s}', value: '7' },
					{ text: '{s name="MobileDetailCommentRate6"}6{/s}', value: '6' },
					{ text: '{s name="MobileDetailCommentRate5"}5{/s}', value: '5' },
					{ text: '{s name="MobileDetailCommentRate4"}4{/s}', value: '4' },
					{ text: '{s name="MobileDetailCommentRate3"}3{/s}', value: '3' },
					{ text: '{s name="MobileDetailCommentRate2"}2{/s}', value: '2' },
					{ text: '{s name="MobileDetailCommentRate1"}1 sehr schlecht{/s}', value: '1' }
				]
			}, {
				xtype: 'textareafield',
				label: '{s name="MobileDetailCommentMessageTitle"}Ihre Meinung{/s}',
				required: true,
				name: 'sVoteComment'
			}
		]
	}, {
		xtype: 'button',
		id: 'voteBtn',
		ui: 'confirm round',
		text: '{s name="MobileDetailCommentSendCommentButton"}Bewertung abgeben{/s}',

		/** TODO - Check fields before submit */
		handler: function() {
			Ext.dispatch({
				controller: 'detail',
				action: 'saveComment'
			});
			return true;
		}
	}],

	initComponent: function() {
		App.views.Shop.commentForm.superclass.initComponent.call(this);
	}
});

/**
 * Last picture zoom pinch
 *
 * @private
 */
var lastpinch = 75;

/**
 * Picture View
 *
 * Creates an carousel, which contains all article pictures
 *
 * @access public
 * @class
 * @extends Ext.Panel
 */
App.views.Shop.pictures = Ext.extend(Ext.Carousel,
/** @lends App.views.Shop.pictures# */
{
	id: 'pictures',
	direction: 'horizontal',
	listeners: {
		scope: this,
		beforeactivate: function(me) {
			me.setLoading(true);
		},
		activate: function(me) {
			me.setLoading(false);
		},
		deactivate: function(me) {
			me.destroy();
		}
	},
	initComponent: function() {
		var me = this, items = [], data = App.stores.Picture.data.items, count = 0,
			ordernumber = document.getElementById('ordernumberDetail').innerHTML;

		// Get Images
		Ext.each(data, function(item, idx) {
			var html = me.filterPicture(item, ordernumber);

			if(Ext.isString(html)) {
				items[count] = new Ext.Panel({
					html: html,
					cls: 'slide_image',
					scroll: false,
					listeners: {
						scope: this,
						el: {
							delegate: '.tapImage',
							doubletap: me.onDblTap
						}
					}
				});
				count++;
			}
		});

		Ext.apply(me, {
			items: [items]
		});
		me.doLayout();

		App.views.Shop.pictures.superclass.initComponent.call(this);
	},
	/**
	 * Resize the current picture to a specific value
	 */
	onDblTap: function() {
		if(lastpinch < 75) {
			return;
		}
		var element = this.query('img');
		Ext.each(element, function(el) {
			lastpinch = 75;
			el.setAttribute('width', lastpinch + '%');
		});
	},

	{literal}
	filterPicture: function(item, ordernumber) {
		var html = '';

		if(!Ext.isEmpty(item.get('relations')) && item.get('relations') != ordernumber) {
			return false;
		}

		html = '<div class="tapImage"><img width="'+lastpinch+'%" src="'+item.get('big_picture')+'"/></div>';
		if(!Ext.isEmpty(item.get('desc'))) {
			html += '<div class="description">'+item.get('desc')+'</div>';
		}

		return html;
	}
	{/literal}
});
</script>