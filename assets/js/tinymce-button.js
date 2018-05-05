;(function( $ ) {
    tinymce.PluginManager.add('wauc_auction', function( editor )
    {
        var shortcodeValues = [];
        shortcodeValues.push({text: 'WooCommerce auction', value:1});

        editor.addButton('wauc_auction', {
            //type: 'listbox',
            text: 'Auction widget (Pro)',
            onclick : function(e){
                $.post(
                    ajaxurl,
                    {
                        action : 'wauc_show_auction_modal',
                    },
                    function(data){
                        $('#wpwrap').append(data);
                    }
                )
            },
            values: shortcodeValues
        });
    });

    var selector = '';

    $(document).on( 'click', '.modal-content .close', function(){
        $(this).parent().parent().remove();
    }).on( 'click', '.sm_shortcode_list li',function(){
        selector = $(this);
        get_shortcode_attr( $(this).data('id'), selector.text(), $('#sm-modal') );
        $('#sm-modal').remove();
    }).on( 'click', '.shortcode_atts_ok', function(){

        tinyMCE.activeEditor.selection.setContent('['+selector.text().trim()+' ' + atts_string + '][/'+selector.text().trim()+']' );

        $('#sm-modal,#sm-modal-atts').remove();
    }).on( 'click', '.shortcode_atts_cancel', function(){
        $('#sm-modal-atts').remove();
    });
}(jQuery));
