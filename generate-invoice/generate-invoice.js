 let ajaxurl = "wp-admin/admin-ajax.php";
(function( $ ) {
    'use strict';
    $(document).ready(function(){
        $("#gi-save-product").click(function(e) {
            //e.preventDefault();  
               
            let product_name = $('#gi-pdt-name').val();
            let product_quantity = $('#gi-pdt-qty').val();
            let unit_price = $('#gi-unit-price').val();
            let tax = $('#gi-tax').val();

            let info = {
                'action': 'ginvoice',
                'product_name': product_name,
                'product_quantity' : product_quantity,
                'unit_price' : unit_price,
                'tax' : tax
            };
                
            
            jQuery.ajax({
                type: "POST",
                url: ajaxurl, 
                data: info,
                beforeSend: function() {            
                },
                success: function(res){  
                    location.reload(true);
                   
                },
                error: function(res){
                },
            }); 

        }); 

        $("#gi_discount_amount").change(function(){
            var discount = $(this).val();
            var total_amount = $('#ig-sub-total-tax').html();
            var discount_amt = (total_amount*discount)/100;
            var new_sub_total = total_amount - discount_amt;

            $('#ig-total-amount').html(parseFloat(new_sub_total).toFixed(2));
        });
        $('#gi-generate-invoice').click(function(){
            var discount = $('#gi_discount_amount').val();
            $('#discount_amount-td').html(discount);
            var divToPrint = document.getElementById('gi-table-wrapper');
            var newWin = window.open('','Print-Window');
            newWin.document.open();
            newWin.document.write('<html><body onload="window.print()">'+divToPrint.innerHTML+'</body></html>');
            newWin.document.close();
            setTimeout(function(){newWin.close();},10);
        });

    });
})( jQuery );


