jQuery(document).ready(function($) {
    $( "#sortable" ).sortable();
    $( "#sortable" ).disableSelection();

    $("#save_order").on('click', function(){
        let nid = $(this).data('nid');
        //console.log('nid:' + nid);
        let order = $( "#sortable" ).sortable( "toArray" );
        //alert(JSON.stringify(order));
        $.ajax({
             cache: false,
             type: "POST",
             url: "/api/update_order/" + nid,
             dataType: "json",
             contentType: "application/json;charset=utf-8",
             data: JSON.stringify(order),
             success: function(data) {
               console.log(data);
               location.reload(); // reload page
             },
             error: function() {
               console.log('result: error' + data);
             }
         });
    });
});
