$(async function () {
    "use strict";
    /** #################### -- The base JS part should be on top of all JS files --  #################### **/
    // Language Translator
    let base_url = $('#base_url').val();
    let language_name = $('#language_name').val();
    let language_path = '/resources/lang/' + language_name + '.json';
    let language_file = base_url + language_path;
    let language_key = {};

    async function loadLanguage() {
      try {
        const res = await fetch(language_file);
        language_key = await res.json();
      } catch (err) {
        console.error('Failed to load language file:', err);
      }
    }
    await loadLanguage();


    // Company Info
    let company_data = $('#company_data').val();
    let company_session_data = {};
    try {
        company_session_data = JSON.parse(company_data);
    } catch (e) {
        console.error('Error parsing company info:', e);
    }
    /** #################### -- The base JS part should be on top of all JS files --  #################### **/


    // Initialize Select2 for outlet + payment method dropdowns
    ['from_outlet_id', 'to_outlet_id', 'payment_method_id'].forEach(function (id) {
        if ($('#' + id).length && !$('#' + id).hasClass('select2-hidden-accessible')) {
            $('#' + id).select2({
                dropdownParent: $('#' + id).closest('.card-body'),
                width: '100%'
            });
        }
    });

    // Load balance on page load if both outlets are already selected (edit mode)
    function maybeLoadBalance() {
        const toOutletId = $('#to_outlet_id').val();
        const fromOutletId = $('#from_outlet_id').val();
        if (toOutletId) {
            loadOutletBalance(toOutletId, fromOutletId);
        }
    }
    maybeLoadBalance();

    // Reload balance whenever either outlet selection changes
    $(document).on('change', '#to_outlet_id, #from_outlet_id', function() {
        maybeLoadBalance();
    });

    function loadOutletBalance(toOutletId, fromOutletId) {
        if (!toOutletId) {
            $('#outlet_balance_display').hide();
            return;
        }

        const baseUrl = $('#base_url').val() || '';
        let routeUrl = baseUrl + '/outlet/get-balance-by-id/' + toOutletId;
        if (fromOutletId) {
            routeUrl += '?from_outlet_id=' + fromOutletId;
        }

        $.ajax({
            url: routeUrl,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success && response.data) {
                    const balance = parseFloat(response.data.balance || 0);
                    const balanceType = balance >= 0 ? 'Debit' : 'Credit';
                    const balanceAmount = Math.abs(balance);

                    if (balance == 0) {
                       $('#outlet_balance_display').hide();
                       return;
                    }

                    const formattedAmount = balanceAmount.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    const toName = $('#to_outlet_id option:selected').text().trim() || 'To outlet';
                    const fromName = fromOutletId ? ($('#from_outlet_id option:selected').text().trim() || 'From outlet') : '';
                    let message;
                    if (fromName) {
                        message = balance > 0 ? (toName + ' owes ' + fromName) : (fromName + ' owes ' + toName);
                    } else {
                        message = balance > 0 ? (toName + ' owes (net)') : (toName + ' is owed (net)');
                    }

                    $('#outlet_balance_amount').text(formattedAmount);
                    $('#outlet_balance_type').text(message);

                    $('#outlet_balance_type').removeClass('bg-label-success bg-label-danger bg-label-warning');
                    if (balanceType === 'Debit') {
                        $('#outlet_balance_type').addClass('bg-label-danger');
                    } else {
                        $('#outlet_balance_type').addClass('bg-label-success');
                    }

                    $('#outlet_balance_display').show();
                } else {
                    $('#outlet_balance_display').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading outlet balance:', error);
                $('#outlet_balance_display').hide();
            }
        });
    }

});
