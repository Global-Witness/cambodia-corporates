function formswitch()  {
    
    var fs_switcher = $('.form-switch.switcher'); // the field to listen on
    
    /** listen for changes on the switcher **/
    fs_switcher.change(function(e) { 
        /** get the fields to display **/
        var fs_chosen = fs_switcher.val();
    
        /** hide current field **/
        $('.form-switch.switchable').hide();
        /** reset all fields **/
        $('.form-switch.switchable input').val('');
        $('.form-switch.switchable select#nationality').val('');
        $('select#nationality').selectpicker('render');
        
        /** display the selected form field **/
        $('.form-switch.switchable.' + fs_chosen ).fadeIn('fast');
        
    });
    
}
 
$(document).ready(function() {    
    
    /** Watch on Form Changes **/
    formswitch();
    
    /** Toggle Project Info on home/project page **/
    toggleIt();
    
    /** Trigger Bootstrap Tooltips **/
    $('[data-toggle="tooltip"]').tooltip();
}); 
 
function toggleIt(){
    var toggler     = $('.read-more');
    var toToggle    = $('.read-more-target');
    var hideToggled = $('.hide-read-more');
    
    toggler.click(function(e) {
        e.preventDefault();
        if(toToggle.hasClass('hidden')){ 
            toToggle.removeClass('hidden').addClass('show');
            toggler.addClass('hidden');
        }
        return false;
    });
    
    hideToggled.click(function(e) { 
        e.preventDefault();
        if(toToggle.hasClass('show')) { 
            toToggle.removeClass('show').addClass('hidden');
            toggler.removeClass('hidden').css({'display': 'inline-block'});
        }
        return false;
    });
}