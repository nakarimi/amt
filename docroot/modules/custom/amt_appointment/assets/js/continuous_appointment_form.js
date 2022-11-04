(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.amt_continuous_appointment_form = {

    attach: function(context, settings) {
      if (context !== document) {
        return;
      }
      if (this.init) {
        this.init(context);
        this.init = undefined;
      }
    },

    init: function (context) {
      this.prependClickEvent($(context).find('#edit-submit'), function(event) {
        if (Drupal.behaviors.amt_continuous_appointment_form.confirm) {
          event.stopPropagation();
          event.preventDefault();
          Drupal.behaviors.amt_continuous_appointment_form.confirm(context);
        }
      });
    },

    prependClickEvent: function ($element, handler) {
      return $element.each(function (container) {
        $element = $(this);
        $element.on('click', handler);
        container = $._data($element.get(0), 'events').click;
        container.unshift(container.pop());
      });
    },

    // Shows Confirmation dialog
    confirm: function(context) {
      var buttons = {};
      buttons[Drupal.t('Yes')] = function () {
        $(this).dialog('destroy');
        Drupal.behaviors.amt_continuous_appointment_form.confirm = undefined;
        $(context).find('#edit-submit').click();
      };

      buttons[Drupal.t('No')] = function () {
        $(this).dialog('destroy');
      };

      return $('<div id="continuous-confirm">' +
        Drupal.t('Are you sure to Change all future lesson appointments?') +
        '</div>'
      ).dialog({
        modal: true,
        closeOnEscape: false,
        width: 'auto',
        dialogClass: 'continuous-dialog',
        title: Drupal.t('Confirmation'),
        buttons: buttons,
        close: function (event, ui) {}
      });
    }
  };
})(jQuery, Drupal);
