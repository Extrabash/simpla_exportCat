{capture name=tabs}
    {if in_array('import', $manager->permissions)}<li><a href="index.php?module=ImportAdmin">Импорт</a></li>{/if}
    <li><a href="index.php?module=ExportAdmin">Экспорт</a></li>
    <li class="active"><a href="index.php?module=ExportAdmin">Экспорт Категорий</a></li>
    {if in_array('backup', $manager->permissions)}<li><a href="index.php?module=BackupAdmin">Бекап</a></li>{/if}
{/capture}
{$meta_title='Экспорт товаров' scope=parent}

<script src="{$config->root_url}/simpla/design/js/piecon/piecon.js"></script>
<script>
    {literal}
        var in_process = false;

        $(function() {

            $('input[type="checkbox"]').change(function() {

                check_deeper($(this), $(this).attr('level'));
                check_shalower($(this), $(this).attr('level'));

            });

            function check_deeper(box, level) {
                let parent_row = box.parents('.row[level="' + level + '"]');
                // Найдем вложенный список
                let kids = parent_row.find('.small_list input[type="checkbox"]');
                if (kids.length > 0) {
                    kids.prop('checked', box.prop('checked'));
                    kids.toggleClass('pseudo', box.prop('checked'));
                }
            }

            function check_shalower(box, level) {
                if (level > 0) {
                    let new_level = level - 1;

                    let parent_row = box.parents('.row[level="' + new_level + '"]');

                    // Найдем вложенный список
                    let kids = parent_row.find('.small_list input[type="checkbox"]');
                    let kids_flag = true;
                    // Если врубили обратно, проверим, все ли четко с остальным списком
                    if (box.prop('checked') && kids.length > 0) {
                        kids.each(function() {
                            if (!$(this).prop('checked'))
                                kids_flag = false;
                        });
                    }

                    let main_mf = parent_row.find('input[level="' + new_level + '"]');
                    if (box.prop('checked') && kids_flag)
                        main_mf.prop('checked', true);
                    else
                        main_mf.prop('checked', false);

                    kids.toggleClass('pseudo', (box.prop('checked') && kids_flag));


                    if (new_level > 0)
                        check_shalower(main_mf, new_level)
                }
            }

            function get_current_cats() {
                let all_cats = '';
                let length = $('input[type="checkbox"]:checked:not(.pseudo)').length;
                $('input[type="checkbox"]:checked:not(.pseudo)').each(function(index, element) {
                    all_cats += $(this).val();
                    if (index !== (length - 1))
                        all_cats += ',';
                })
                return all_cats;
            }

            // On document load
            $('input#start').click(function() {



                let cats = get_current_cats();
                if (cats.length > 0) {
                    Piecon.setOptions({fallback: 'force'});
                    Piecon.setProgress(0);
                    $("#progressbar").show('fast');
                    $("#progressbar").progressbar({ value: 0 });

                    $("#start").hide('fast');

                    do_export(1, cats);
                    // Заблокируем чекбоксы
                    $('input[type="checkbox"]').prop('disabled', true);
                } else
                    alert('Выберите категории')

            });

            function do_export(page, filter_categories, img_domain = document.location.origin) {
                page = typeof(page) != 'undefined' ? page : 1;

                $.ajax({
                    url: "ajax/export.php",
                    data: {page:page, filter_categories:filter_categories, img_domain:img_domain},
                    dataType: 'json',
                    success: function(data) {

                        if (data && !data.end) {
                            Piecon.setProgress(Math.round(100 * data.page / data.totalpages));
                            $("#progressbar").progressbar({ value: 100 * data.page / data.totalpages });
                            do_export(data.page * 1 + 1, filter_categories);
                        } else {
                            if (data && data.end) {
                                Piecon.setProgress(100);
                                $("#progressbar").hide('fast');
                                window.location.href = 'files/export/export.csv';

                                // Разблокируем чекбоксы
                                $('input[type="checkbox"]').prop('disabled', false);
                                $("#start").show('slow');
                            }
                        }
                    },
                    error: function(xhr, status, errorThrown) {
                        alert(errorThrown + '\n' + xhr.responseText);
                    }

                });

            }
        });

    {/literal}
</script>
{literal}
    <style>
        .ui-progressbar-value {
            background-image: url(design/images/progress.gif);
            background-position: left;
            border-color: #009ae2;
        }

        #progressbar {
            clear: both;
            height: 29px;
        }

        #result {
            clear: both;
            width: 100%;
        }

        #download {
            display: none;
            clear: both;
        }

        #list.small_list .cell {
            padding-top: 4px;
            padding-bottom: 1px;
        }

        .cell.fat {
            font-weight: bold;
        }

        #list.small_list input[type="checkbox"i] {
            margin-top: 1px;
        }

        .pseudo {
            opacity: 0.6;
        }
    </style>
{/literal}

{if $message_error}
    <!-- Системное сообщение -->
    <div class="message message_error">
        <span>
            {if $message_error == 'no_permission'}Установите права на запись в папку {$export_files_dir}
            {else}{$message_error}
            {/if}
        </span>
    </div>
    <!-- Системное сообщение (The End)-->
{/if}


<div>
    <h1>Экспорт товаров</h1>
    {if $message_error != 'no_permission'}

        {if $categories}
            <br style="clear: both;" />
            <br />
            <hr />
            <h2>Выберите категории</h2>
            {function name=categories_tree level=0}
                {if $categories}
                    <div id="list" class="small_list">

                        {foreach $categories as $category}
                            <div class="row" level="{$level}">
                                <div class="tree_row">
                                    <label for="check_{$category->id}">
                                        <div class="cell" style="margin-left:{$level*20}px"></div>
                                        <div class="checkbox cell">
                                            <input type="checkbox" id="check_{$category->id}" name="check[]" value="{$category->id}"
                                                level="{$level}" />
                                        </div>
                                        <div class="cell {if $category->subcategories}fat{/if}">
                                            {$category->name|escape}
                                        </div>
                                        <div class="clear"></div>
                                    </label>
                                </div>
                                {categories_tree categories=$category->subcategories level=$level+1}
                            </div>
                        {/foreach}

                    </div>
                {/if}
            {/function}
            {categories_tree categories=$categories}
        {/if}

        <div id='progressbar'></div>
        <input class="button_green" id="start" type="button" name="" value="Экспортировать" />
    {/if}
</div>