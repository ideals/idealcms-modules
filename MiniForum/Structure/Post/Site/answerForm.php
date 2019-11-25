<div class="postAnswer" id="postAnswer<?php echo $formValues['ID'];?>">
    <form method="post" id="postForm<?php echo $formValues['ID'];?>" action="javascript: void(0)">
        <div class="input-line">
            <label for="authorPF">Ваше имя:<em class="formee-req"> *</em></label>
            <input id="authorPF" value="" name="author" type="text">
        </div>
        <div class="input-line">
            <label for="emailPF">E-mail: </label>
            <input id="emailPF" name="email" value="" type="text">
        </div>
        <div class="input-line">
            <label for="contentPF">Сообщение:<em class="formee-req"> *</em> </label>
            <textarea name="content"></textarea>
        </div>
        <div class="input-line" style="display: <?php if (isSet($formValues['isPosterBlock'])) echo 'block'; else echo 'none'; ?>">
        <?php
        if (isset($_REQUEST['isAuthorized']) && $_REQUEST['isAuthorized'] == "true") {
            echo '<label for="authorPF">Ответ от постера: </label><input type="checkbox" name="is_poster" value="true">';
        }
        ?>
        </div>
        <label for="getMail">Получить уведомление об ответе: </label><input type="checkbox" id="getMail" name="is_mail" checked  value="true">
        <div class="post-btn input-line">
            <button name="sendPost" id="sendPost" type="submit" onclick='<?php echo $formValues['buttonMethod'] ;?>(<?php
                                                                                                                if (isset($formValues['ajaxAddNewPost'])) echo 'true,'; else echo 'true,';
                                                                                                                if ($formValues['buttonMethod'] == 'ajaxAddNewPost') echo '"' . $formValues['pageStructure']. '"'  . ",";
                                                                                                                if ($formValues['buttonMethod'] == 'ajaxAddNewPost') echo $formValues['mainParentId'] . "," ;
                                                                                                                echo $formValues['ID'];
                                                                                                            ?>);'>Отправить</button>
            <button type="submit" onclick="loadForm.closeAnswerForm(<?php echo $formValues['ID']; ?>); return false;">Закрыть</button>
        </div>
        <input type="hidden" name="ID" value="<?php if (isSet($formValues['ID'])) echo $formValues['ID']; else echo '0'; ?>">
        <input type="hidden" name="parent_id" value="<?php if (isSet($formValues['ID'])) echo $formValues['ID']; else echo '0'; ?>">
        <input type="hidden" name="main_parent_id" value="<?php if (isSet($formValues['mainParentId'])) echo $formValues['mainParentId']; else echo '0'; ?>">
        <input type="hidden" name="page_structure" value="<?php if (isSet($formValues['pageStructure'])) echo $formValues['pageStructure']; else echo '0'; ?>">
    </form>
</div>