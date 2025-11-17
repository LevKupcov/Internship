<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>

<?if ($arResult["isFormErrors"] == "Y"):?>
    <div style="color: #ff5555; margin-bottom: 20px;">
        <?=$arResult["FORM_ERRORS_TEXT"];?>
    </div>
<?endif;?>

<?if ($arResult["isFormNote"] != "Y"):?>
    <form action="<?=POST_FORM_ACTION_URI?>" method="POST" class="js-validated-form">
        <?=bitrix_sessid_post()?>
        
        <?if (!empty($arResult["QUESTIONS"])):?>
            <?foreach ($arResult["QUESTIONS"] as $FIELD_SID => $arQuestion):?>
                <div class="popup-feedback__input-cover">
                    <label class="popup-feedback__input-label">
                        <?=$arQuestion["CAPTION"]?><?if ($arQuestion["REQUIRED"] == "Y"):?><span style="color: #d4a574;">*</span><?endif?>
                    </label>
                    
                    <?if ($arQuestion["STRUCTURE"][0]["FIELD_TYPE"] == "textarea"):?>
                        <textarea 
                            name="form_<?=$arQuestion["STRUCTURE"][0]["FIELD_TYPE"]?>_<?=$arQuestion["STRUCTURE"][0]["ID"]?>" 
                            class="popup-feedback__textarea"
                            <?if ($arQuestion["REQUIRED"] == "Y"):?>required<?endif?>
                        ><?=$arQuestion["STRUCTURE"][0]["VALUE"]?></textarea>
                    <?else:?>
                        <input 
                            type="<?=($FIELD_SID == 'EMAIL' ? 'email' : ($FIELD_SID == 'PHONE' ? 'tel' : 'text'))?>" 
                            name="form_<?=$arQuestion["STRUCTURE"][0]["FIELD_TYPE"]?>_<?=$arQuestion["STRUCTURE"][0]["ID"]?>" 
                            value="<?=$arQuestion["STRUCTURE"][0]["VALUE"]?>"
                            class="popup-feedback__input <?=($FIELD_SID == 'PHONE' ? 'mask-phone-js' : '')?>"
                            <?if ($arQuestion["REQUIRED"] == "Y"):?>required<?endif?>
                        />
                    <?endif?>
                </div>
            <?endforeach;?>
        <?else:?>
            <div class="popup-feedback__input-cover">
                <label class="popup-feedback__input-label">Ваше имя и фамилия:</label>
                <input type="text" class="popup-feedback__input js-validated-field" data-validated_name="name" />
            </div>

            <div class="popup-feedback__double-column">
                <div class="popup-feedback__input-cover">
                    <label class="popup-feedback__input-label">Телефон</label>
                    <input type="tel" class="popup-feedback__input mask-phone-js js-validated-field" data-validated_name="phone" />
                </div>
                <div class="popup-feedback__input-cover">
                    <label class="popup-feedback__input-label">Email</label>
                    <input type="mail" class="popup-feedback__input js-validated-field" data-validated_name="mail" />
                </div>
            </div>

            <div class="popup-feedback__input-cover">
                <label class="popup-feedback__input-label">Название компании:</label>
                <input type="text" class="popup-feedback__input" />
            </div>

            <div class="popup-feedback__input-cover">
                <label class="popup-feedback__input-label">Опишите Вашу задачу:</label>
                <textarea class="popup-feedback__textarea"></textarea>
            </div>
        <?endif;?>

        <div class="popup-feedback__consent">
            <div class="popup-feedback__consent-form-wrapper">
                <input class="popup-feedback__consent-input" id="consent-calc-1" type="checkbox" checked="checked" required />
                <label class="popup-feedback__consent-form" for="consent-calc-1">
                    <a href="/article-data-processing.html" target="_blank">
                        Нажимая кнопку «Отправить», я даю свое согласие на обработку моих персональных данных, в соответствии с Федеральным законом от 27.07.2006 года №152-ФЗ «О персональных данных», на условиях и для целей, определенных в Согласии на обработку персональных данных.
                    </a>
                </label>
            </div>
            <div class="popup-feedback__consent-form-wrapper">
                <input class="popup-feedback__consent-input" id="consent-calc-2" type="checkbox" checked="checked" required />
                <label class="popup-feedback__consent-form" for="consent-calc-2">
                    <a href="/article-privacy-policy.html" target="_blank">
                        Оставляя данные на Сайте, заполняя регистрационную форму, Вы соглашаетесь с настоящей Политикой конфиденциальности.
                    </a>
                </label>
            </div>
        </div>

        <div class="popup-feedback__button-cover">
            <input type="hidden" name="web_form_submit" value="Отправить" />
            <button type="submit" class="button button_modal-gold js-button-submit">
                ОТПРАВИТЬ
            </button>
        </div>
    </form>
<?else:?>
    <div style="color: #4CAF50; padding: 30px; text-align: center; font-size: 18px;">
        <?=$arResult["FORM_NOTE"]?>
    </div>
<?endif;?>

