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