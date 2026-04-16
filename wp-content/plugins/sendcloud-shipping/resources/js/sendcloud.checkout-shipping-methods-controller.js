(function ($) {
    'use strict';
    window.addEventListener('load', function() {
        let shippingFields = document.getElementById('shipping-fields');
        if (!shippingFields) {
            return;
        }

        addEventListeners();
        addObserverForShippingRates();
        if (navigator.userAgent.indexOf("Firefox") === -1) {
            initialize();
            isInitialized = true;
        }
    });

    let isInitialized = false;
    let $selectedShippingMethod,
        shippingMethodSelectorPrefix,
        mountElement,
        deliveryMethod,
        locale,
        state,
        renderedWidgetDestructor;
    let widgetStates = {}, responseData;

    /**
     * Add event listeners.
     */
    function addEventListeners() {
        document.addEventListener('change', function (event) {
            initialize();
            window.renderScShippingOptionModule.then(onCheckoutUpdate);
        });
        let shippingPostCode = document.getElementById('shipping-postcode'),
            shippingCity = document.getElementById('shipping-city');
        if (shippingPostCode) {
            shippingPostCode.addEventListener('change', (event) => {
                if (SendcloudShippingData) {
                    SendcloudShippingData['postal_code'] = shippingPostCode.value;
                }
            });
        }

        if (shippingCity) {
            shippingCity.addEventListener('change', (event) => {
                if (SendcloudShippingData) {
                    SendcloudShippingData['city'] = shippingCity.value;
                }
            });
        }
    }

    function addObserverForShippingRates() {
        var shippingOptions = document.getElementsByClassName('wc-block-components-shipping-rates-control');
        var targetElement = shippingOptions[0];
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                // Check if nodes were added
                if (mutation.type === 'childList' && mutation.addedNodes.length === 1) {
                    if (mutation.addedNodes[0].classList.contains("wc-block-components-radio-control") ||
                        mutation.addedNodes[0].classList.contains("wc-block-components-radio-control__option") ||
                        mutation.addedNodes[0].classList.contains("wc-block-components-radio-control__option-layout") ||
                        !isInitialized) {
                        isInitialized = true;
                        initialize();
                    }
                } else if (mutation.target.nodeType === 3) {
                    initialize();
                }
            });
        });

        var observerConfig = {childList: true, subtree: true, characterData: true};
        observer.observe(targetElement, observerConfig);
    }

    /**
     * Initializes Sendcloud shipping methods on the block checkout.
     */
    function initialize() {
        const shippingOptions = document.getElementsByClassName('wc-block-components-shipping-rates-control').item(0).children[0].children[0];
        if (!shippingOptions) {
            return;
        }

        const shippingMethodsIds = getShippingMethodIds(shippingOptions);
        const initializeBlockCheckoutUrl = document.getElementById('sendcloud-block-checkout-initialize-endpoint').value;

        $.post(initializeBlockCheckoutUrl, JSON.stringify(shippingMethodsIds), function (response) {
            let methodDetails = Object.entries(response['method_details']);
            const locale = response['locale'];
            Array.from(methodDetails).forEach(details => {
                if (details[1].length === 0 ||
                    (details[1]['delivery_method_type'].slice(0, -'_delivery'.length) !== 'nominated_day' &&
                        details[1]['delivery_method_type'].slice(0, -'_delivery'.length) !== 'service_point')) {
                    return;
                }

                let option, dataDiv;
                let inputValue = 'sc_' + details[1]['delivery_method_type'].slice(0, -'_delivery'.length) + ':' + details[0];
                option = document.querySelector("input[value='" + inputValue + "']");
                if (option) {
                    dataDiv = option.parentElement.querySelector("div[class='wc-block-components-radio-control__label-group']")
                } else {
                    dataDiv = shippingOptions.querySelector("div[class='wc-block-components-radio-control__label-group']");
                }

                let idPrefix = 'shipping_method_0_sc_' + details[1]['delivery_method_type'].slice(0, -'_delivery'.length) + details[0];
                let mountPointDiv = document.createElement("div");
                mountPointDiv.id = idPrefix + '_mount_point';
                mountPointDiv.classList.add('sc-delivery-method-mount-point');
                dataDiv.lastChild.before(mountPointDiv);
                let localeInput = document.createElement('input');
                localeInput.type = 'hidden';
                localeInput.id = idPrefix + '_locale';
                localeInput.value = locale;
                dataDiv.lastChild.before(localeInput);
                let submitDataInput = document.createElement('input');
                submitDataInput.type = 'hidden';
                submitDataInput.id = idPrefix + '_submit_data';
                submitDataInput.name = 'sendcloudshipping_widget_submit_data[sc_' + details[1]['delivery_method_type'].slice(0, -'_delivery'.length) + details[0] + ']';
                dataDiv.lastChild.before(submitDataInput);
                let deliveryMethodScript = document.createElement("script");
                deliveryMethodScript.id = idPrefix + '_delivery_method';
                deliveryMethodScript.type = "application/json";
                deliveryMethodScript.textContent = JSON.stringify(details[1]);
                dataDiv.lastChild.before(deliveryMethodScript);
            });

            responseData = response;
            onCheckoutUpdate();
        });
    }

    function getSelectedMethod() {
        let selectedShippingMethod = findSelectedShippingMethod();
        if (selectedShippingMethod) {
            return selectedShippingMethod;
        }

        if (responseData['selected_shipping_method']) {
            selectedShippingMethod = responseData['method_details'][responseData['selected_shipping_method']];
            if (selectedShippingMethod && selectedShippingMethod['delivery_method_type']) {
                return 'sc_' + responseData['method_details'][responseData['selected_shipping_method']]['delivery_method_type'].slice(0, -'_delivery'.length) + ':' + responseData['selected_shipping_method'];
            }
        }

        return null;
    }

    /**
     * Get IDs of shipping methods which are rendered on checkout
     * @param shippingMethodOptions
     * @returns {*[]}
     */
    function getShippingMethodIds(shippingMethodOptions) {
        var ids = [];
        if (shippingMethodOptions.children.length > 1) {
            Array.from(shippingMethodOptions.children).forEach(option => {
                let element = option.children[0];
                if (element instanceof HTMLInputElement && (element.value.startsWith('sc_') || element.value.includes('service_point_shipping_method'))) {
                    ids.push(parseInt(element.value.split(':')[1]));
                }
            });
        }

        return ids;
    }

    function onCheckoutUpdate() {
        if (renderedWidgetDestructor) {
            renderedWidgetDestructor.call();
        }

        $selectedShippingMethod = getSelectedMethod();
        if (!$selectedShippingMethod) {
            return;
        }

        let shippingMethod = $selectedShippingMethod.replace(':', '');
        shippingMethodSelectorPrefix = 'shipping_method_0_' + shippingMethod;

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

    /**
     * @return {jQuery|HTMLElement|*}
     */
    function findSelectedShippingMethod() {
        let shippingMethodContainerDiv = document.querySelector('.wc-block-components-shipping-rates-control');
        let checkedRadioButton = shippingMethodContainerDiv.querySelector('input[type="radio"]:checked');

        if (!checkedRadioButton) {
            return;
        }

        return checkedRadioButton.value;
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

        }).then(function (destructorCallback) {
            renderedWidgetDestructor = destructorCallback;
        });
    }

    function onShippingOptionChange(event) {

        widgetStates[shippingMethodSelectorPrefix] = event.detail.state;

        let $selectionDataElement = $(`#${shippingMethodSelectorPrefix}_submit_data`);
        if (0 !== $selectionDataElement.length) {
            $selectionDataElement.val(JSON.stringify({...deliveryMethod, ...event.detail.data}));
        }

        const saveDeliveryMethodDataUrl = document.getElementById('sendcloud-block-checkout-save-delivery-method-data-endpoint').value;
        $.post(saveDeliveryMethodDataUrl, JSON.stringify({...deliveryMethod, ...event.detail.data}), function (response) {
        });
    }
})(jQuery);