<?php
/**
 * Shows a shipment line
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr class="shipment" data-shipment_id="<?php echo $shipment->id; ?>">
  <td><?php echo $shipment->status ?></td>
  
  <td>
    <?php
      $address_type = 'shipper';
      include( 'html-shipment-address-cell.php');
    ?>
  </td>
  
  <td>
    <?php
      $address_type = 'recipient';
      include( 'html-shipment-address-cell.php');
    ?>
  </td>
  
  <td>
    <?php
      $parcels = $shipment->parcels;
      foreach( $parcels as $key => $parcel ) {
        ?>
        <div class="parcel" data-parcel_index="<?php echo $key; ?>">
          <div class="parcel-data">
            <p>
              <?php echo MFB_Shipment::formatted_parcel_line( $parcel ); ?>
              
              <?php if ( $shipment->status == 'mfb-draft' ) { ?>
                <a class="edit_parcel" href="#"><img src="<?php echo WC()->plugin_url() ?>/assets/images/icons/edit.png" alt="<?php _e( 'Edit shipper address', 'my-flying-box' ) ?>" width="14" /></a>
                <?php if ( $key > 0) { ?>
                <a class="delete_parcel" href="#"></a>
                <?php } ?>
              <?php } ?>
            </p>
          </div>
          <div class="parcel-form" style="display: none;">
            <input name="_parcel_<?php echo $key; ?>_length" placeholder="<?php _e("l", 'my-flying-box') ?>" type="text" value="<?php echo $parcel->length; ?>" style="width: 40px; text-align: center;"/>x
            <input name="_parcel_<?php echo $key; ?>_width" placeholder="<?php _e("w", 'my-flying-box') ?>" type="text" value="<?php echo $parcel->width; ?>" style="width: 40px; text-align: center;"/>x
            <input name="_parcel_<?php echo $key; ?>_height" placeholder="<?php _e("h", 'my-flying-box') ?>" type="text" value="<?php echo $parcel->height; ?>" style="width: 40px; text-align: center;"/>cm
            <input name="_parcel_<?php echo $key; ?>_weight" placeholder="<?php _e("weight", 'my-flying-box') ?>" type="text" value="<?php echo $parcel->weight; ?>" style="width: 40px; text-align: center;"/>kg
            <br/>
            <input name="_parcel_<?php echo $key; ?>_description" placeholder="<?php _e("goods description", 'my-flying-box') ?>" type="text" value="<?php echo $parcel->description; ?>"/>
            <br/>
            <input name="_parcel_<?php echo $key; ?>_value" placeholder="<?php _e("value", 'my-flying-box') ?>" type="text" value="<?php echo $parcel->value; ?>" style="width: 60px; text-align: right;"/>€
            <?php $countries = WC()->countries->__get( 'countries' ) ?>
              <?php _e("Origin:", 'my-flying-box') ?><select name="_parcel_<?php echo $key; ?>_country_of_origin" style="width: 120px;">
              <option value='<?php echo $parcel->country_of_origin ?>' selected><?php echo $countries[$parcel->country_of_origin] ?></option>
              <option value='' disabled></option>
              <?php foreach ($countries as $code => $name) {
                echo "<option value='$code'>$name</option>";
              }
              ?>
            </select>
            <br/>
            <button class="button cancel_parcel_form"><?php _e( 'Cancel', 'my-flying-box' ) ?></button>
            <button class="button submit_parcel_form"><?php _e( 'Submit', 'my-flying-box' ) ?></button>
          </div>
        </div>
      <?php
      }
      
    if ( $shipment->status == 'mfb-draft' ) {
    ?>
        <div class="parcel" data-parcel_index="new">
          <div class="parcel-data">
            <p>
              <button class="button new_parcel"><?php _e( 'Add new parcel', 'my-flying-box' ) ?></button>
            </p>
          </div>
          <div class="parcel-form" style="display: none;">
            <input name="_parcel_new_length" placeholder="<?php _e("l", 'my-flying-box') ?>" type="text" style="width: 40px; text-align: center;"/>x
            <input name="_parcel_new_width" placeholder="<?php _e("w", 'my-flying-box') ?>" type="text" style="width: 40px; text-align: center;"/>x
            <input name="_parcel_new_height" placeholder="<?php _e("h", 'my-flying-box') ?>" type="text" style="width: 40px; text-align: center;"/>cm
            <input name="_parcel_new_weight" placeholder="<?php _e("weight", 'my-flying-box') ?>" type="text" style="width: 40px; text-align: center;"/>kg
            <br/>
            <input name="_parcel_new_description" placeholder="<?php _e("goods description", 'my-flying-box') ?>" type="text"/>
            <br/>
            <input name="_parcel_new_value" placeholder="<?php _e("value", 'my-flying-box') ?>" type="text" style="width: 60px; text-align: right;"/>€
            <?php $countries = WC()->countries->__get( 'countries' ) ?>
              <?php _e("Origin:", 'my-flying-box') ?><select name="_parcel_new_country_of_origin" style="width: 120px;">
              <option value='<?php echo $parcels[0]->country_of_origin ?>' selected><?php echo $countries[$parcels[0]->country_of_origin] ?></option>
              <option value='' disabled></option>
              <?php foreach ($countries as $code => $name) {
                echo "<option value='$code'>$name</option>";
              }
              ?>
            </select>
            <br/>
            <button class="button cancel_parcel_form"><?php _e( 'Cancel', 'my-flying-box' ) ?></button>
            <button class="button submit_parcel_form"><?php _e( 'Submit', 'my-flying-box' ) ?></button>
          </div>
        </div>
      <?php } ?>
  </td> 
  
  <td>
    <?php if ( $shipment->status == 'mfb-draft' ) { ?>
    <div class="mfb-available-offers">
      <?php $offers = $shipment->quote->offers ?>
        <select name="_mfb_selected_offer" class="offer-selector" style="width: 250px; font-size: 0.9em;">
        <?php foreach ($offers as $offer) {
          echo "<option data-offer_id='".$offer->id."' value='".$offer->product_code."'";
          if ( $shipment->offer && $shipment->offer->product_code == $offer->product_code ) echo " selected";
          echo ">".$offer->product_name." - ".$offer->formatted_price()."</option>";
        }
        ?>
      </select>
      
      <?php
        if (true == $shipment->offer->pickup) {
          // A pickup is required, we must select the date
          echo '<p>';
          _e( 'Select pickup date:', 'my-flying-box' );
          echo '<br/>';
        ?>
        <select name="_mfb_pickup_date" class="pickup-date-selector" style="width: 250px; font-size: 0.9em;">
        <?php
        foreach ( $shipment->offer->collection_dates as $date ) {
          echo "<option value='".$date->date."'>".$date->date."</option>";
        }
        ?>
      </select>
      <?php
        echo '</p>';
        }
      ?>
      
      <?php
        if (true == $shipment->offer->relay) {
          // A pickup is required, we must select the date
          echo '<p>';
          _e( 'Select delivery location:', 'my-flying-box' );
          echo '<br/>';
        ?>
        <select name="_mfb_relay_code" class="delivery-location-selector" style="width: 250px; font-size: 0.9em;">
        <?php
        $params = array(
          'street' => $shipment->recipient->street,
          'city' => $shipment->recipient->city
        );
        $locations = $shipment->offer->get_delivery_locations( $params );

        foreach ( $locations as $location ) {
          echo "<option value='".$location->code."'";
          $preferred_location = get_post_meta( $theorder->id, '_mfb_delivery_location');
          if ( $location->code == $preferred_location[0] ) {
            echo " selected";
          }
          echo ">";
          echo $location->code . ' ' . $location->company . ' / ' . $location->street . ' / ' . $location->postal_code.' '.$location->city;
          echo "</option>";
        }
        ?>
      </select>
      <?php
        echo '</p>';
        }
      ?>

      <br/>
      <button type="button" class="button button-primary book-offer"><?php _e( 'Book this service', 'my-flying-box' ); ?></button>
    </div>
        <?php
 
    } else {
    ?>
      <div class="mfb-booked-offer">
        <div class="offer-details" data-offer_id="<?php echo $shipment->offer->id ?>">
          <p><?php echo $shipment->offer->product_name ?> (<?php echo $shipment->offer->formatted_price(); ?>)</p>
          <button type="button" class="button button-primary download-labels"><?php _e( 'Download labels', 'my-flying-box' ); ?></button>
        </div>
        <?php
          $tracking_links = $shipment->tracking_links();
          if ( count ( $tracking_links ) > 0 ) {
            echo '<div class="mfb-order-tracking">';
            echo '<p>'.__( 'Tracking:', 'my-flying-box' );
            foreach( $tracking_links as $link ) {
            ?>
              <br/><a href='<?php echo $link['link']; ?>' target='_blank'><?php echo $link['code']; ?></a>
            <?php
            }
            echo '</p></div>';
          }
        ?>
        
        <?php
        if ( $shipment->delivery_location ) {
          echo '<p>';
          _e('Delivery location:', 'my-flying-box');
          echo '<br/>';
          echo $shipment->delivery_location->company.' ('.$shipment->delivery_location->code.')';
          echo '<br/>';
          echo $shipment->delivery_location->street;
          echo '<br/>';
          echo $shipment->delivery_location->postal_code.' '.$shipment->delivery_location->city;
          echo '</p>';
        }
        
        ?>
        
      </div>
    <?php
    }
      
    ?>
  </td>
  
  <td>
    <?php if ( $shipment->status == 'mfb-draft' ) { ?>
    <a class="delete-shipment" href="#"></a>
    <?php } ?>
  </td>  
  
</tr>
