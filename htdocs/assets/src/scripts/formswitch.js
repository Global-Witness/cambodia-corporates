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