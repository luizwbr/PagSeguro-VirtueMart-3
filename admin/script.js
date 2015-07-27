/**
* MoIP VirtueMart 2.0.x
**/

// botão sim/não
jQuery(document).ready( function(){ 
    jQuery(".cb-enable").click(function(){
        var parent = jQuery(this).parents('.switch');
        jQuery('.cb-disable',parent).removeClass('selected');
        jQuery(this).addClass('selected');
        jQuery('.checkbox',parent).attr('checked', true);
    });
    jQuery(".cb-disable").click(function(){
        var parent = jQuery(this).parents('.switch');
        jQuery('.cb-enable',parent).removeClass('selected');
        jQuery(this).addClass('selected');
        jQuery('.checkbox',parent).attr('checked', false);
    });
});
