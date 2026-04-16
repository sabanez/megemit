/* global jQuery, SendcloudLocaleMessages */
jQuery(function ($) {
    let widgetStates = {};
    let postalCodeInputField = document.getElementById('billing_postcode');

    if (postalCodeInputField) {
        postalCodeInputField.addEventListener('change', (event) => {
            if (SendcloudShippingData) {
                SendcloudShippingData['postal_code'] = postalCodeInputField.value;
            }
        });
    }

    /**
     * @type {jQuery|HTMLElement|*}
     */
    let $checkoutForm = $('form.checkout');

    if (0 === $checkoutForm.length || !window.renderScShippingOptionModule) {
        return;
    }

    let $selectedShippingMethod,
        shippingMethodSelectorPrefix,
        mountElement,
        deliveryMethod,
        locale,
        state,
        renderedWidgetDestructor;

    $(document.body).on('updated_checkout', function () {
        window.renderScShippingOptionModule.then(onCheckoutUpdate);
    });

    function onCheckoutUpdate(){
        if (renderedWidgetDestructor) {
            renderedWidgetDestructor.call();
        }
        $selectedShippingMethod = findSelectedShippingMethod();
        if (0 === $selectedShippingMethod.length) {
            return;
        }

        initShippingMethodSelectorPrefix();

        let deliveryMethodConfig = $(`#${shippingMethodSelectorPrefix}_delivery_method`).html() || null;
        mountElement = $(`#${shippingMethodSelectorPrefix}_mount_point`).get(0);
        deliveryMethod = JSON.parse(deliveryMethodConfig);
        locale = $(`#${shippingMethodSelectorPrefix}_locale`).val() || 'en-US';
        state = widgetStates[shippingMethodSelectorPrefix];

        if (mountElement) {
            mountElement.classList.add('sc-delivery-method-mount-point');
            mountElement.setAttribute('data-sc-delivery-method-type', deliveryMethod.delivery_method_type)
        }

        let descriptionItems = document.getElementsByClassName('sc-delivery-method-description');
        for (let i = 0; i < descriptionItems.length; i++) {
            descriptionItems[i].parentElement.querySelector('label').classList.add('sc-delivery-method-title');
        }

        renderWidget();
    }

    function initShippingMethodSelectorPrefix() {
        shippingMethodSelectorPrefix = $selectedShippingMethod.attr('id');

        // If default prefix does not work try to adopt for the Woo 2.6.X versions where single shipping method id is
        // not generated uniformly as when there are multiple shipping methods available on the checkout (instance id
        // is committed from id attribute for versions 2.6.X)
        if (!$(`#${shippingMethodSelectorPrefix}_delivery_method`).html()) {
            shippingMethodSelectorPrefix += '_' + $selectedShippingMethod.val().replace(/:/g, '');
        }
    }

    /**
     * @return {jQuery|HTMLElement|*}
     */
    function findSelectedShippingMethod() {
        let name = 'shipping_method';
        return $checkoutForm.find(
            `select.${name}, input[name^="${name}"][type="radio"]:checked, input[name^="${name}"][type="hidden"]`
        );
    }

    function renderWidget() {
        if (!mountElement || !deliveryMethod) {
            return;
        }

        $(mountElement).on('scShippingOptionChange', onShippingOptionChange);
        window.renderScShippingOption({
            mountElement: mountElement,
            deliveryMethod: deliveryMethod,
            shippingData: SendcloudShippingData ?? {},
            renderDate: new Date(),
            locale: locale,
            state: state,
            localeMessages: SendcloudLocaleMessages,

        }).then(function(destructorCallback) {
            renderedWidgetDestructor = destructorCallback;
        });
    }

    function onShippingOptionChange(event) {

        widgetStates[shippingMethodSelectorPrefix] = event.detail.state;

        let $selectionDataElement = $(`#${shippingMethodSelectorPrefix}_submit_data`);
        if (0 !== $selectionDataElement.length) {
            $selectionDataElement.val(JSON.stringify({...deliveryMethod, ...event.detail.data}));
        }
    }
});
