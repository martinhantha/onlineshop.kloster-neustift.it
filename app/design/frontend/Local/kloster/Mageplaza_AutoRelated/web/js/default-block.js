/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_AutoRelated
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

define([
    'jquery',
    'mage/storage',
    'Mageplaza_AutoRelated/js/model/impression',
    'jquery/ui',
    'owlCarousel'
], function ($, storage, impressionModel) {
    'use strict';

    $.widget('mageplaza.arp_default_block', {
        options: {
            type: '',
            rule_id: '',
            location: '',
            mode: ''
        },

        /**
         * @private
         */
        _create: function () {
            this.initSlider();
            this.initObserver();

            if (this.options.mode === '1') {
                impressionModel.registerRuleImpression(this.options.rule_id);
            }
        },

        /**
         * generate owl carousel slider
         */
        initSlider: function () {
            var slidesToShow = this.options.number_product_slider,
                arrows = true,
                responsive = {
                    1028: {
                        items: this.options.number_product_slider
                    },
                    640: {
                        items: 3
                    },
                    0: {
                        items: 2
                    }
                };

            if (!this.isSlider()) {
                return this;
            }
            if (this.options.location.indexOf('sidebar') !== -1) {
                slidesToShow = 1;
                responsive = {};
            } else if (this.options.location.indexOf('cross') !== -1) {
                slidesToShow = 4;
            }

            this.element.find('ol').owlCarousel({
                items: slidesToShow,
                rtl: false,
                loop: true,
                autoplay: true,
                nav: arrows,
                dots: false,
                autoplaySpeed: 1000,
                slideBy: this.options.number_product_scrolled,
                responsive: responsive,
                autoplayHoverPause: true,
                center: false
            });
        },

        /**
         * init click observer
         */
        initObserver: function () {
            var clickEl = this.element.find(
                '.mageplaza-autorelated-slider .product-item .slider-product-item-info a, ' +
                '.mageplaza-autorelated-slider .product-item .slider-product-item-info button, ' +
                '.block-content .products-grid .product-item .slider-product-item-info a, ' +
                '.block-content .products-grid .product-item .slider-product-item-info button, ' +
                '.popup-content .popup-right a, ' +
                '.popup-content .popup-right button'
            );

            if (this.isSlider()) {
                clickEl.draggable({
                    start: function () {
                        $(this).addClass('noclick');
                    }
                });
            }

            clickEl.click(function () {
                var id = $(this).parents('.mageplaza-autorelated-block').attr('rule-id');

                if ($(this).hasClass('noclick')) {
                    $(this).removeClass('noclick');
                }
                storage.post('autorelated/ajax/click', JSON.stringify({ ruleId: id }), false);
            });
        },

        isSlider: function () {
            return this.options.type === 'slider';
        }
    });

    return $.mageplaza.arp_default_block;
});
