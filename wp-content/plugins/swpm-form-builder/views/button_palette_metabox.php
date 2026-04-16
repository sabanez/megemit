<div class="taxonomydiv">
    <p>
        <?php _e('Select the standard or custom tab below. Then click on a field to add it to the form.', 'swpm-form-builder'); ?> 
        <span id="form-spinner" class="spinner"></span>
    </p>
    <ul class="posttype-tabs add-menu-item-tabs" id="swpm-field-tabs">
        <li class="tabs"><a href="#standard-fields" class="nav-tab-link swpm-field-types"><?php _e('Standard', 'swpm-form-builder'); ?></a></li>
        <li class=""><a href="#custom-fields" class="nav-tab-link swpm-field-types"><?php _e('Custom', 'swpm-form-builder'); ?></a></li>
    </ul>
    <!--<ul class="posttype-tabs add-menu-item-tabs" id="swpm-field-tabs">
        <li class="tabs"><a href="#standard-fields" class="nav-tab-link swpm-field-types"><?php _e('Standard', 'swpm-form-builder'); ?></a></li>
    </ul>-->
    <div id="standard-fields" class="tabs-panel tabs-panel-active">
        <ul class="swpm-fields-col-1">
            <!--<li><a href="#" class="swpm-draggable-form-items" data-type="text" data-key="user_name" id="form-element-text"><b></b>Username</a></li>-->
            <li><a href="#" class="swpm-draggable-form-items" data-type="text" data-key="first_name" id="form-element-text"><b></b>First Name</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="text" data-key="last_name" id="form-element-text"><b></b>Last Name</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="select" data-key="gender" id="form-element-select"><b></b>Gender</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="text" data-key="company_name" id="form-element-text"><b></b>Company</a></li>            
        </ul>
        <ul class="swpm-fields-col-2">
            <li><a href="#" class="swpm-draggable-form-items" data-type="address" data-key="primary_address" id="form-element-address"><b></b>Address</a></li>            
            <li><a href="#" class="swpm-draggable-form-items" data-type="phone" data-key="primary_phone" id="form-element-phone"><b></b>Phone</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="file-upload" data-key="profile_image" id="form-element-file"><b></b>Profile Image</a></li>
            <!--<li><a href="#" class="swpm-draggable-form-items" data-type="select" data-key="title" id="form-element-select"><b></b>Title</a></li>-->
        </ul>
        <div class="clear"></div>
    </div> <!-- #standard-fields -->
    <div id="custom-fields" class="tabs-panel tabs-panel-inactive">
        <ul class="swpm-fields-col-1">
            <li><a href="#" class="swpm-draggable-form-items" data-key="fieldset" data-type="fieldset" id="form-element-fieldset">Fieldset</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-key="custom" data-type="text" id="form-element-text"><b></b>Text</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-key="custom" data-type="checkbox" id="form-element-checkbox"><b></b>Checkbox</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-key="custom" data-type="select" id="form-element-select"><b></b>Select</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-key="custom" data-type="date" id="form-element-datepicker"><b></b>Date</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-key="custom" data-type="url" id="form-element-url"><b></b>URL</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-key="custom" data-type="number" id="form-element-digits"><b></b>Number</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-key="custom" data-type="phone" id="form-element-phone"><b></b>Phone</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-key="custom" data-type="file-upload" id="form-element-file"><b></b>File Upload</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-key="custom" data-type="country" id="form-element-country"><b></b>Country</a></li>
        </ul>
        <ul class="swpm-fields-col-2">
            <li><a href="#" class="swpm-draggable-form-items" data-type="section"      data-key="section" id="form-element-section">Section</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="textarea"     data-key="custom" id="form-element-textarea"><b></b>Textarea</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="radio"        data-key="custom" id="form-element-radio"><b></b>Radio</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="address"      data-key="custom" id="form-element-address"><b></b>Address</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="email"        data-key="custom" id="form-element-email"><b></b>Email</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="currency"     data-key="custom" id="form-element-currency"><b></b>Currency</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="time"         data-key="custom" id="form-element-time"><b></b>Time</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="html"         data-key="custom" id="form-element-html"><b></b>HTML</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="instructions" data-key="instructions" id="form-element-instructions"><b></b>Instructions</a></li>
            <li><a href="#" class="swpm-draggable-form-items" data-type="member_id"    data-key="member_id" id="form-element-digits"><b></b>Member ID</a></li>
        </ul>
        <div class="clear"></div>
    </div> <!-- #custom-fields -->
</div> <!-- .taxonomydiv -->
<div class="clear"></div>