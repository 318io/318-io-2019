jQuery(document).ready(function($) {

    let result = [];

    $( "#selectable" ).selectable({
      stop: function() {
        result = [];
        $(".ui-selected", this).each(function() {
           let v = $(this).attr("id");
           if(v != undefined) result.push(v);
        });
      }
    });

    $("#add_collections").on('click', function(){
      let nid = $(this).data('nid');
      console.log(result);

      $.ajax({
           cache: false,
           type: "POST",
           url: "/api/add_collection/" + nid,
           dataType: "json",
           contentType: "application/json;charset=utf-8",
           data: JSON.stringify(result),
           success: function(data) {
             console.log(data);
             location.reload(); // reload page
           },
           error: function() {
             console.log('result: error' + data);
           }
       });
    });


    $("#delete_collections").on('click', function(){
      let nid = $(this).data('nid');
      console.log(result);

      $.ajax({
           cache: false,
           type: "POST",
           url: "/api/del_collection/" + nid,
           dataType: "json",
           contentType: "application/json;charset=utf-8",
           data: JSON.stringify(result),
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
