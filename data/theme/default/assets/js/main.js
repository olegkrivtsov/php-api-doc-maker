$(document).ready(function(){
    
    $('.dir-name').on('click', function(){
        $(this).parent().children('.directory, .file').toggle();
    });
    
});