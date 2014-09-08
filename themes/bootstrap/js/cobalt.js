var Cobalt = {

    init: function() {
        this.bindPopovers();
        this.bindTooltips();
        this.bindDropdownItems();
        this.bindDatepickers();
        this.initFormSave();
        this.initDataTables();
    },

    bindPopovers: function() {
        var selector = '[data-toggle="popover"]';
        jQuery.each(jQuery(selector), function(i, popover) {
            popover = jQuery(popover);
            var options = {
                html : true,
                container: "body",
                content: function() {
                    var contentClass = popover.attr('data-content-class');
                    if (contentClass) {
                        return $('.'+contentClass).html();
                    }
                }
            };
            popover.popover(options);
        });
    },

    bindTooltips: function() {
        $('[rel="tooltip"]').tooltip();
    },

    bindDatepickers: function() {
        jQuery('.date_input').datepicker({
            format:userDateFormat,
        });
        jQuery(".date_input").on('changeDate',function(event){
            var selectedYear = event.date.getFullYear(),
                selectedMonth = event.date.getMonth()+ 1,
                selectedDay = event.date.getDate(),
                date = selectedYear+"-"+selectedMonth+"-"+selectedDay;

            jQuery("#"+jQuery(event.currentTarget).attr('id')+'_hidden').val(date);
            jQuery(this).datepicker('hide');

            if ( jQuery(this).hasClass('editable-modal-datepicker') ){
                Cobalt.sumbitForm(jQuery(this).closest('form'));
            }
        });
    },

    getFormSubmitOptions: function() {
        return { 
            beforeSubmit: function(arr, $form, options) {      
                // add attributes necessary for AJAX call
                arr.push({'name': 'format', 'value': 'raw'});
                arr.push({'name': 'tmpl', 'value': 'component'});         
            },
            success:   function(response, status, xhr, $form) {
                Cobalt.onSaveSuccess(response, status, xhr, $form);
            },
            type:      'post',
            dataType:  'json'
        }; 
    },

    initDataTables: function() {
        var options = {
            'processing': true,
            'serverSide': true,
            'ajax': 'index.php?format=raw&task=datatable&loc='+loc,
            'fnDrawCallback': function(oSettings) {
                Cobalt.bindPopovers();
            }
        };

        if (typeof dataTableColumns === 'object') {
            options.columns = dataTableColumns;
        }

        jQuery('table.data-table').dataTable(options);
    },

    initFormSave: function(options) {
        // initialize jQuery form submit plugin
     
        if(!options) {
            otpions = this.getFormSubmitOptions();
        }

        // bind form using 'ajaxForm' 
        jQuery('form[data-ajax="1"]').submit(function() { 
            jQuery(this).ajaxSubmit(options); 
            return false; 
        }); 
    },

    onSaveSuccess: function(response) {
        if (typeof response.alert !== 'undefined') {
            Cobalt.modalMessage(Joomla.JText._('COM_PANTASSO_SUCCESS_HEADER'), response.alert.message, response.alert.type);
        }
        // Update info in various HTML tags
        if (typeof response.item !== 'undefined') {
            $('.modal').modal('hide');
            Cobalt.updateStuff(response.item);
        }
        // Remove rows from table
        if (typeof response.remove !== 'undefined') {
            Cobalt.removeRows(response.remove);
        }
    },

    sumbitModalForm: function(button) {
        var modal = jQuery(button).closest('.modal');
        this.sumbitForm(modal.find('form'));
        modal.modal('hide');
    },

    sumbitForm: function(form) {
        jQuery(form).ajaxSubmit(Cobalt.getFormSubmitOptions());
        // prevent from submitting form
        return false;
    },

    save: function(data) {
        jQuery.post('index.php', data, function(response) {
            try {
                response = $.parseJSON(response);
            } catch (e) {
                // not json
            }
            Cobalt.onSaveSuccess(response);
        });
    },

    updateStuff: function(data) {
        var itemId = data.id;
        jQuery.each(data, function(name, value) {
            if (value === null) {
                value = '';
            }
            var element = jQuery('#'+name+'_'+itemId);
            var field = jQuery('[name="'+name+'"]');
            if (element.length) {
                element.text(value);
            }
            if (field.length) {
                field.val(value);
            }
        });
    },

    removeRows: function(ids) {
        jQuery.each(ids, function(i, id) {
            jQuery('#list_row_'+id).hide('fast', function() {
                jQuery(this).remove();
            });
        });
    },

    modalMessage: function (heading, message, type, autoclose) {
        var html = '<div class="alert alert-flying alert-'+type+' alert-dismissible" role="alert">';
        html += '<button type="button" class="close" data-dismiss="alert">';
        html += '<span aria-hidden="true">&times;</span>';
        html += '</button>';
        if (typeof heading !== 'undefined') {
            html += '<strong>'+heading+'</strong>';
        }
        if (typeof message !== 'undefined') {
            html += message;
        }
        html += '</div>';
        var alert = jQuery(html);
        jQuery('body').append(alert);
        alert.animate({top: "60px", opacity: 1}, 300);
        if (autoclose !== false) {
            setTimeout(function() {
                alert.animate({top: "0px" ,opacity: 0}, 300);
            }, 2000);
        }
    },

    newListItem: function (data, type) {

        id = "id="+data.id;

        switch ( type ){
            case "deal":
                var loc = "deals";
                break;
            case "company":
                var loc = "companies";
                break;
            case "people":
                var loc ="people";
                break;
            default:
                var loc = type;
                break;
        }

        jQuery.ajax({
            type:'post',
            url:'index.php?view='+loc+'&format=raw&layout=entry&'+id,
            dataType:'html',
            success:function(html){
                var taskItem = jQuery('a.task_list_'+data.id),
                    listItem = jQuery('#list_row_'+data.id);

                if (type == "tasklist") {
                    taskItem.html(data.name);
                    taskItem.effect("highlight",2000);
                }else{
                    if (listItem.length != 0) {
                        listItem.replaceWith(html);
                    }else{
                        jQuery("#list").prepend(html);
                    }

                    listItem.find('td').effect('highlight', 2000);
                }

                Cobalt.bindDatepickers();
                Cobalt.bindTooltips();
                Cobalt.bindPopovers();
            }
        });
    },

    bindDropdownItems: function () {
        jQuery('.dropdown_item').click(function() {
            var link = jQuery(this),
                data = {
                    'model': link.attr('data-item'),
                    'id': link.attr('data-item-id'),
                    'task': 'save',
                    'format': 'raw'
                };
                data[link.attr('data-field')] = link.attr('data-value');

            Cobalt.save(data);
        });
    },

    deleteListItems: function() {
        var itemIds = [];
        jQuery("input[name='ids\\[\\]']:checked").each(function() {
            itemIds.push(jQuery(this).val());
        });
        var data = {'item_id': itemIds,'item_type': loc, 'task': 'trash', 'format': 'raw'};
        Cobalt.save(data);
        // showAjaxLoader();
        // jQuery.ajax({
        //     type:'POST',
        //     url:'index.php?task=trash&tmpl=component&format=raw',
        //     data: { item_id : itemIds, item_type : loc },
        //     dataType:'JSON',
        //     success:function(data){
        //         if ( data.success ){
        //             jQuery.each(itemIds,function(key,value){
        //                 jQuery("#list_row_"+value).remove();
        //             });
        //             modalMessage(Joomla.JText._('COBALT_SUCCESS_MESSAGE'));
        //         }
        //         hideAjaxLoader();
        //     }
        // });
    }
};

window.onload = function () {
    Cobalt.init();
};

/**
 * Global functions
 **/

function ucwords(str) {
  return (str + '')
    .replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function($1) {
      return $1.toUpperCase();
    });
}

var Joomla = {
    JText: {
            strings: {},
            '_': function(key, def) {
                return typeof this.strings[key.toUpperCase()] !== 'undefined' ? this.strings[key.toUpperCase()] : def;
        },
        load: function(object) {
            for (var key in object) {
                this.strings[key.toUpperCase()] = object[key];
            }
            return this;
        }
    }
};
