<?php
    $cs = App()->clientScript;
    $cs->registerScriptFile(App()->params['bower-asset'] . '/ajaxq/ajaxq.js');
    $cs->registerCssFile(App()->theme->baseUrl . '/css/csvimport.css');
    echo TbHtml::beginFormTb(TbHtml::FORM_LAYOUT_VERTICAL, ['participants/import', 'step' => 'map'], 'post', ['enctype' => 'multipart/form-data', 'id' => 'importForm']);
?>
<div class="overlay">
    <span>Please wait for the worker thread to end. This can take a while depending on your configured chunk size.</span>
</div>
<div class="row form-horizontal" id="participantForm">
    <div class="col-md-3 col-md-offset-2">
        <?php
        /** @var CActiveDataProvider $attributes */
        App()->clientScript->registerScriptFile(App()->params['bower-asset'] . '/papaparse/papaparse.js');
        echo TbHtml::fileFieldControlGroup('file', null, [
            'label' => gT("CSV File"),
            'required' => true,
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL,
            'labelWidthClass' => 'col-sm-6',
            'controlWidthClass' => 'col-sm-6',
        ]);

        echo TbHtml::dropDownListControlGroup('encoding', '', aEncodingsArray(), [
            'empty' => gT("Automatic"),
            'label' => 'File encoding',
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL,
            'labelWidthClass' => 'col-sm-6',
            'controlWidthClass' => 'col-sm-6',
        ]);
        echo TbHtml::dropDownListControlGroup('separator', '', [
            "," => gT("Comma"),
            ";" => gT("Semicolon"),
            "\t" => gT("Tab"),
        ], [
            'empty' => gT("Automatic"),
            'label' => gT("Separator"),
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL,
            'labelWidthClass' => 'col-sm-6',
            'controlWidthClass' => 'col-sm-6',
        ]);

        echo TbHtml::numberFieldControlGroup('batchSize', 5000, [
            'label' => gT("Batch size for uploading"),
            'required' => true,
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL,
            'labelWidthClass' => 'col-sm-6',
            'controlWidthClass' => 'col-sm-6',
//            'help' => gT("Bigger chunks will give less frequent status updates but have a (slightly) better performance."),

        ]);
        echo TbHtml::numberFieldControlGroup('querySize', 2500, [
            'label' => gT("Batch size for queries"),
            'required' => true,
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL,
            'labelWidthClass' => 'col-sm-6',
            'controlWidthClass' => 'col-sm-6',
//            'help' => gT("Bigger batches will increase memory usage for better performance."),
        ]);
        echo TbHtml::numberFieldControlGroup('chunkSize', 1024*1024, [
            'label' => gT("Chunk size for reading"),
            'required' => true,
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL,
//            'help' => gT("Bigger chunks will give less frequent status updates but have a (slightly) better performance."),
            'labelWidthClass' => 'col-sm-6',
            'controlWidthClass' => 'col-sm-6',
        ]);

        echo TbHtml::checkBoxControlGroup("filterBlanks", true, [
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL,
            'label' => gT("Filter blank email addresses"),
            'help' => gT("If not enabled, empty addresses will throw an error during import."),
            'labelWidthClass' => 'col-sm-6',
            'controlWidthClass' => 'col-sm-6',
        ]);
        ?>
    </div>
    <div class="col-md-5">
        <?php
        echo TbHtml::checkBoxControlGroup("headerColumns", true, [
            'label' => gT("File has headers"),
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL
        ]);

        echo TbHtml::customControlGroup(TbHtml::tag('table', ['id' => 'preview', 'class' => 'table'], ''), 'preview', [
            'label' => 'Data preview:',
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL
        ]);
        echo TbHtml::textAreaControlGroup('errors', '', [
            'label' => 'Data errors:',
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL
        ]);

        ?>
    </div>
</div>
<?=$this->renderPartial('map'); ?>
<div class="row">
    <div class="col-md-8 col-md-offset-2" style="margin-bottom:15px; margin-top:15px;">
        <div class="btn-row pull-right">
        <?php

        echo Html::buttonRow([
            gT('Import participants') => [
                'color' => 'primary',
                'class' => 'not-busy'
            ],
            gT('Restart import') => [
                'type' => Html::BUTTON_TYPE_SUBMIT,
                'id' => 'restart',
                'class' => 'stopped'

            ],
            gT('Reconfigure') => [
                'id' => 'reconfigure',
                'type' => Html::BUTTON_TYPE_HTML,
                'class' => 'stopped',
                'color' => 'primary'
            ],

            gT('Stop import') => [
                'type' => Html::BUTTON_TYPE_HTML,
                'color' => 'danger',
                'id' => 'stop',
                'class' => 'busy not-stopped'
            ],

        ]);

        ?>
        </div>
    </div>
    <div class="col-md-8 col-md-offset-2" id="progress">
        <?php
        echo TbHtml::customControlGroup(TbHtml::progressBar(0, [
            'barOptions' => ['id' => 'readProgress']
        ]), '', [
            'label' => gT("File read progress"),
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL
        ]);
        echo TbHtml::customControlGroup(TbHtml::progressBar(0, [
            'barOptions' => ['id' => 'sendProgress']
        ]), '', [
            'label' => gT("File upload progress"),
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL
        ]);
        echo TbHtml::customControlGroup(TbHtml::tag('div', [
            'id' => 'resourceUsage',
            'class' => 'resourceUsage'
        ], ''), '', [
            'label' => gT("PHP Resource usage (memory / time)"),
            'formLayout' => TbHtml::FORM_LAYOUT_HORIZONTAL
        ]);
        ?>

    </div>
</div>

<?=TbHtml::endForm(); ?>
<script type="text/javascript">
    getConfig = function() {
        return {
            chunkSize: $('#chunkSize').val(),
            dynamicTyping: false,
            worker: true,
            encoding: $('#encoding').val(),
            delimiter: $('#separator').val(),
            header: $('#headerColumns').is(':checked'),
            skipEmptyLines: true
        };
    };

    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        runImport();
    });

    $('#stop').on('click', function(e) {
        console.log('aborted');
        $.ajaxq.abort('csvimport');
        if ($('#readProgress').data('progress') < 100) {
            $('#importForm').addClass('aborted');
        }
        $('#importForm').addClass('stopped')

    });

    $('#reconfigure').on('click', function(e) {
        $('#importForm').removeClass('busy').removeClass('stopped');
    })

    function runImport() {
        // Get map.
        var map = {};
        $('#importForm').addClass('busy').removeClass('aborted').removeClass('stopped');
        $('#resourceUsage').empty();
        $('#sendProgress').data('progress', 0);
        $('#sendProgress').css('width', 0);
        $('#readProgress').css('width', 0);
        $('body').animate({scrollTop: 0}, 'slow');
        var batchSize = $('#batchSize').val();
        $('.csvColumn').filter(function(i, elem) { return $(elem).find('input').val() != ''; }).each(function(i, elem) {
            map[$(elem).attr('data-column')] = $(elem).find('input').val();
        });



        var config = getConfig();
        var queue = [];
        var sendData = function(data, progress) {
            var $progress = $('#sendProgress');
            $.ajaxq('csvimport', {
                url: "<?=App()->createUrl('participants/ajaximport');?>",
                data: data,
                method: 'post',
                timeout: 0,
                contentType: 'application/json',
                error: function(jqXHR, textStatus, errorThrown) {
                    $.ajaxq.abort('csvimport');

                },
                success: function(data) {
                    if ($.ajaxq.isRunning('csvimport')) {
                        $progress.data('progress', $progress.data('progress') + 100 * progress);
                    } else {
                        $progress.data('progress', 100);
                    }

                    $('<div/>').addClass('memory').css('height', (data.memory * 100).toPrecision(2)).appendTo($('#resourceUsage'));
                    $('<div/>').addClass('time').css('height', (data.time * 100).toPrecision(2)).appendTo($('#resourceUsage'));
                    $('#resourceUsage').children().css('width', (100 / $('#resourceUsage').children().length) + '%');

                    $progress.css('width', $progress.data('progress') + '%');
                    if ($progress.data('progress') == 100) {
                        $('#importForm').addClass('stopped')
                        $.notify(
                            "Import complete!"
                        , {
                            type: 'success'
                        });
                    }

                }
            });
        };

        var filterBlanks = $('#filterBlanks').is(':checked');

        var fileSize = $('#file')[0].files[0].size;
        config.chunk = function(result, reader) {
            if ($('#importForm').is('.aborted')) {
                reader.abort();
                $('#importForm').removeClass('aborted');
                return;
            }
            var progress = (result.meta.cursor / fileSize * 100);
            $('#readProgress').data('progress', progress).css('width', progress + '%');

            var rows = [];
            for (var i = 0; i < result.data.length; i++) {
                var row = {};
                if (filterBlanks && result.data[i].email.length > 0) {

                    for (var key in map) {
                        row[map[key]] = result.data[i][key];
                    }
                    rows.push(row);
                }

                if (rows.length == batchSize) {
                    var data = JSON.stringify({
                        'items': rows,
                        'map' : map,
                        'querySize': parseInt($('#querySize').val()),
                        'YII_CSRF_TOKEN': $('input[name=YII_CSRF_TOKEN]').val()
                    });
                    rows = [];
                    sendData(data, batchSize / result.data.length * (config.chunkSize > fileSize ? 1 : config.chunkSize / fileSize));
                    i++;
                }
            }
            var data = JSON.stringify({
                'items': rows,
                'map' : map,
                'YII_CSRF_TOKEN': $('input[name=YII_CSRF_TOKEN]').val()
            });
            sendData(data, rows.length / result.data.length * (config.chunkSize > fileSize ? 1 : config.chunkSize / fileSize));
        }


        var start = Date.now();
        config.complete = function() {
            console.log('Finished reading in ' + (Date.now() - start) / 1000 + ' seconds');
        };

        console.log($('#file').parse({config: config}));

        console.log(map);
    }
    $('#participantForm').on('change', 'input, select', function(e) {
        var removeColumns = function() {
            $('.csvColumn').remove();
            $('#preview').empty();
            $('#errors').empty();
        }
        var addColumn = function(name, $head) {
            $head.append('<th>' + name + '</th>');
            var $container = $('#csvColumns');

            var $column = $('#columnPlaceholder').clone();
            $column.css('display', '');
            $column.removeAttr('id');
            $column.addClass('csvColumn');
            $column.attr('data-column', name);
            if (name.length > 0) {
                $column.find('label').text(name);
            } else {
                $column.find('label').text("Empty");
            }

            $('#existingAttributes [data-attribute]').each(function(i, elem) {
                if ($(elem).data('attribute').toUpperCase() == name.toUpperCase()) {
                    $column.find('input').val($(elem).data('attribute'));
                    $container = $(elem);
                }
            });
            $column.appendTo($container);


        }
        $file = $(e.delegateTarget).find('input[type=file]');

        if ($file.val() == "") {
            removeColumns();
            return;
        }


        var config = getConfig();
        config.preview = 5;
        config.complete = function(result) {
            removeColumns();
            var $errors = $('#errors');
            if (result.errors.length > 0) {
                var text = '';
                for(var i = 0; i < result.errors.length; i++) {
                    text += "[" + result.errors[i].type + "] " + result.errors[i].message + " in line " + result.errors[i].row + "\n";

                }
                $errors.text(text);
                $errors.closest('.form-group').removeClass('has-success').addClass('has-error');
            } else {
                $errors.closest('.form-group').removeClass('has-error').addClass('has-success');
            }

            var $head = $('<thead/>');
            for (var column in result.data[0]) {

                addColumn(column, $head);
            }
            $head.appendTo('#preview');
            $body = $('<tbody/>');
            for (var r in result.data) {
                var $row = $('<tr/>');
                for (var column in result.data[r]) {
                    if (result.data[r][column].length <= 50) {
                        $row.append('<td>' + result.data[r][column] + '</td>');
                    } else {
                        $row.append('<td>' + result.data[r][column].substr(0, 47) + '...</td>');
                    }
                }
                $row.appendTo($body);
            }
            $body.appendTo('#preview');
        };
        $file.parse({config: config});
    })
</script>