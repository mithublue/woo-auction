(function ($) {
    $(document).ready(function () {

        var wauc_admin = {
            init : function(){
                wauc_admin.role_list_show_hide($(':checkbox[name="wauc_auction_role_enabled"]'),'p.wauc_auction_roles_field');
                $(':checkbox[name="wauc_auction_role_enabled"]').change(function(){
                    wauc_admin.role_list_show_hide($(this),'p.wauc_auction_roles_field');
                });

                $('.datepicker').datetimepicker(
                    {defaultDate: "",
                        dateFormat: "yy-mm-dd",
                        numberOfMonths: 1,
                        showButtonPanel: true,
                        showOn: "button",
                        //buttonImage: wauc_admin_data.wauc_admin_data,
                        buttonText : 'Picke a time',
                        buttonImageOnly: true
                    });

            },
            role_list_show_hide : function( checkbox, binder ) {
                if( checkbox.is(':checked') ) {
                    $(binder).show();
                } else {
                    $(binder).hide();
                }
            }
        }

        wauc_admin.init();
    });
}(jQuery));