(function() {
    tinymce.create('tinymce.plugins.learndash_shortcodes_tinymce', {
        init: function(ed, url) {
            ed.addButton('learndash_shortcodes_tinymce', {
                title: 'LearnDash Shortcodes',
				icon: 'icon dashicons-desktop',
                /* image: url.substring(0, url.length - 3) + "/images/tinyMC_icon_003.png", */
				
                onclick: function() {
                    //learndash_shortcode_ref = ed.selection;
					learndash_shortcodes.tinymce_callback( ed.selection );
                }
            });
        },
        createControl: function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('learndash_shortcodes_tinymce', tinymce.plugins.learndash_shortcodes_tinymce);
})();