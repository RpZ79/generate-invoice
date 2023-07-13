<?php
/**
 * Plugin Name: Generate Invoice
 * Plugin URI: 
 * Description: Invoice generating Plugin
 * Version: 1.0.0
 * Author: Roopasree
 * Author URI:
 * Text Domain: generate-invoice
 *
 * WC requires at least: 5.0
 * WC tested up to: 7.7
*/

if(!defined('WPINC')) { 
    die; 
}

 /**
 * Enqueue script and style.
 */
add_action('wp_enqueue_scripts', 'enqueue_styles_and_scripts');
function enqueue_styles_and_scripts() {
    wp_enqueue_style( 'generate-invoice-style', plugin_dir_url(__FILE__).'generate-invoice-style.css', array(), '1.0.0', true );
    wp_enqueue_script( 'generate_invoice_script', plugin_dir_url(__FILE__).'generate-invoice.js', array(), '1.0.0', true );
}

/** 
 * create database. 
 */
register_activation_hook(__FILE__, 'gi_create_database');
function gi_create_database() {        
    global $wpdb;
    if(is_null($wpdb)) { return false; }
    $charset_collate = $wpdb->get_charset_collate();
    $gi_table_name = $wpdb->prefix . 'gi_product_data';
        
    # create cache table    
    $sql1 = "CREATE TABLE {$gi_table_name} (
        id bigint(20) unsigned NOT NULL auto_increment ,
        uid varchar(64) NOT NULL,
        p_name varchar(64) NOT NULL,
        quantity bigint(20) NOT NULL, 
        unit_price bigint(20) NOT NULL, 
        tax bigint(20) NOT NULL, 
        line_total bigint(20) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY uid (uid)
        ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql1 );
    
    # test if at least one table exists
    if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $gi_table_name)) === $gi_table_name) {
        
    } else {
    }

}

/** 
 * Function to create product list.
 */
function create_product_shortcode() {
    ?>
    <div class="main-wrapper">
       <!--  <div class="sub-wrap">
            <button>Add New Product</button>
        </div> -->
        <div class="sub-wrap">
            Product Name: <input type="text" name="name" id="gi-pdt-name" value=""></br>
        </div>
        <div class="sub-wrap">
            Quantity: <input type="number" name="quantity" id="gi-pdt-qty" value=""></br>
        </div>
        <div class="sub-wrap">
            Unit Price(<span class="ig-sub-info">$</span>): <input type="number" name="unit_price" id="gi-unit-price" value=""></br>
        </div>
        <div class="sub-wrap" >
            tax(<span class="ig-sub-info">should be one of these 0%, 1%, 5%, 10%</span>): <input type="number" name="tax" id="gi-tax" value="" onkeypress="return /^-?[0-9]*$/.test(this.value+event.key)"></br>
        </div>
        <div class="sub-wrap">
            <input type="submit" name="save_product" id="gi-save-product">
        </div>
    </div>
<?php
    echo view_product_data();
}
add_shortcode('generate_invoice', 'create_product_shortcode');


add_action('wp_ajax_ginvoice', 'ginvoice');
add_action('wp_ajax_nopriv_ginvoice', 'ginvoice');  

/** 
 * Ajax function to insert product data
 */
function ginvoice(){   
    global $wpdb;
    $output = ['status' => 1];
    $product_name = '';
    $product_quantity = '';
    $unit_price = '';
    $tax = '';
    $product_name = sanitize_text_field($_POST["product_name"]);
    $product_quantity = sanitize_text_field($_POST["product_quantity"]);
    $unit_price = sanitize_text_field($_POST["unit_price"]);
    $tax = sanitize_text_field($_POST["tax"]);
    $line_total = $product_quantity*$unit_price;

    $table = $wpdb->prefix . 'gi_product_data';
    $data = array(
        'uid'=>'gi_'.$product_name,
        'p_name' => $product_name,
        'quantity' => $product_quantity,
        'unit_price' => $unit_price,
        'tax' => $tax,
        'line_total' =>$line_total,
        
    );

    $wpdb->insert($table, $data);
    $output['status'] = 2;
    header("Location: /index.php");
    wp_send_json($output);
    exit();
    

}
/** 
 *  Function for calculate tax.
 */
function calculate_cost($tax, $price) {
     $sales_tax = $tax/100;
     return $price + ($price * $sales_tax);
}

/** 
 *  Function for view product data
 */
function view_product_data(){ 
    global $wpdb;
    $table = $wpdb->prefix . 'gi_product_data';
    $results = $wpdb->get_results( "SELECT * FROM $table");
    ?>
    <div id="gi-table-wrapper">
    <table style="margin-top: 100px;">
        <tr>
            <th style="border: 1px solid #c8c5c5; padding: 15px;">Product</th>
            <th style="border: 1px solid #c8c5c5; padding: 15px;">Quantity</th>
            <th style="border: 1px solid #c8c5c5; padding: 15px;">Unit Price</th>
            <th style="border: 1px solid #c8c5c5; padding: 15px;">Tax</th>
            <th style="border: 1px solid #c8c5c5; padding: 15px;">Line Total</th>
        </tr>
        <?php  
        foreach($results as $key => $gi_value){
            $gi_array = json_decode(json_encode($gi_value), true);
            $p_name = $gi_array['p_name'];
            $quantity = $gi_array['quantity'];
            $unit_price = $gi_array['unit_price'];
            $tax = $gi_array['tax'];
            $line_total = $gi_array['line_total'];
            ?>
    
            
            <tr>
                <td><?php echo $p_name; ?></td>
                <td><?php echo $quantity;  ?></td>
                <td><?php echo $unit_price; ?></td>
                <td><?php echo $tax; ?></td>
                <td><?php echo $line_total; ?></td>
            </tr>
        <?php } ?>
    </table>
    <table>
        <tr>
            <th>Subtotal without tax</th>
            <?php 
            $sub_total = 0; 
            $total_tax = 0;
            $sub_total_tax = 0;
            foreach($results as $key => $gi_value){
                $gi_array = json_decode(json_encode($gi_value), true);
                $line_total = $gi_array['line_total'];
                $unit_price = $gi_array['unit_price'];
                $quantity = $gi_array['quantity'];               
                
                $sub_total = $line_total+$sub_total;
                
                $tax = $gi_array['tax'];  
                $total_tax = $tax+$total_tax;

                $single_pdt_with_tax = calculate_cost($tax, $unit_price);
                $line_total_with_tax = $quantity * $single_pdt_with_tax;
                
                $sub_total_tax  = $line_total_with_tax +$sub_total_tax;  
            }
                       
            ?>
             <td style="font-weight: bold;"><?php echo $sub_total; ?></td>
            <th style="padding-left: 10px;">Discount Amount(<span class="ig-sub-info">%</span>)</th>
            <td id="discount_amount-td"><input type="text" name="discount_amount" id="gi_discount_amount" value=""></td>
            <th style="padding-left: 10px;">Subtotal with tax</th>
            <td id="ig-sub-total-tax" style="font-weight: bold;"><?php echo $sub_total_tax; ?></td>
        </tr>
        <tr>
            <th></th><td></td>
            <th></th><td></td>
            <th>Total Amount:</th>
            <td id="ig-total-amount" style="font-weight: bold;"><?php echo $sub_total_tax; ?></td>
        </tr>
    </table>
</div>
    <table>
        <tr>
           <td ><button id="gi-generate-invoice">Generate Invoice</button></td> 
        </tr>
    </table>
<?php }    



# Error log.
function write_log ($log)  {
    if (true === WP_DEBUG) {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}

?>

<style>
.sub-wrap {
    padding: 10px;
}

input#gi-pdt-name {
    margin-left: 8%;
}
input#gi-pdt-qty {
    margin-left: 13%;
}
input#gi-unit-price {
    margin-left: 10%;
}
.ig-sub-info {
    font-style: italic;
    font-size: 14px;
}
.main-wrapper {
    font-size: 16px;
    color: #494747;
}
</style>


