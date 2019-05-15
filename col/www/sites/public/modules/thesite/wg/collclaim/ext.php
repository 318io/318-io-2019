<?php

function _collection_claim_method($form, &$form_state, $collection_id) {
    global $user;
    $uid = $user->uid;

    $pre_form_state = variable_get("claim-form_state-{$uid}-{$collection_id}", false);

    if($pre_form_state) {
        $statement = _collection_claim_statement($pre_form_state);
    } else {
        drupal_goto("/collection/identify/{$collection_id}");
    }    

    $statement2 =<<<WHAT
    <a href="/identification_qa" target="_blank">什麼是紙本指認 / 線上指認？</a>
WHAT;

    $form['desc'] = array(
        '#type' => 'item',
        '#title' => t(''),
        '#markup' => $statement
    );

    $form['desc2'] = array(
        '#type' => 'item',
        '#title' => t(''),
        '#markup' => $statement2
    );

    $form['collection_id'] = array(
        '#type' => 'hidden',
        '#value' => $collection_id
    );  
  
    $form['submit1'] = array(
        '#type' => 'submit',
        '#value' => t('紙本指認'),
        '#submit' => array('tranditional_claim'),
    );

    $form['submit2'] = array(
        '#type' => 'submit',
        '#value' => t('線上指認'),
        '#submit' => array('online_claim'),
    );

    $form['submit3'] = array(
        '#type' => 'submit',
        '#value' => t('回上頁'),
        '#submit' => array('modify_claim'),
    );


    return $form;
}

function modify_claim($form, &$form_state) {
    $collection_id = $form_state['values']['collection_id'];
    drupal_goto("/collection/identify/{$collection_id}");
}

function tranditional_claim($form, &$form_state) {
    global $user;
    $uid           = $user->uid;
    $collection_id = $form_state['values']['collection_id'];

    $pre_form_state = variable_get("claim-form_state-{$uid}-{$collection_id}", false);

    if($pre_form_state) {
        _collection_claim_later_submit($pre_form_state);
    } else {
        drupal_set_message('tranditional_claim(): please follow identification steps.');
        drupal_goto("/collection/identify/{$collection_id}");
    }
    

    //drupal_goto('/');
}

function online_claim($form, &$form_state) {
    global $user;
    $uid           = $user->uid;
    $collection_id = $form_state['values']['collection_id'];

    drupal_goto("collection/online-identify/{$collection_id}");
}

function _online_collection_claim($form, &$form_state, $collection_id) {
    $form['name'] = array(
        '#type' => 'textfield',
        '#title' => t('請輸入您的真實姓名, 以便完成指認。'),
        '#size' => 60,
        '#maxlength' => 128,
    );

    $form['collection_id'] = array(
        '#type' => 'hidden',
        '#value' => $collection_id
    );  

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('submit'),
    );  
    return $form;
}

  

function _online_collection_claim_submit($form, &$form_state) {
    global $user;
    $user = user_load($user->uid);
    
    $uid = $user->uid;
    $collection_id = $form_state['values']['collection_id'];
    $user_input = trim($form_state['values']['name']);

    if($user_input == $user->field_realname['und'][0]['value']) {
        // 指認成功

        $pre_form_state = variable_get("claim-form_state-{$uid}-{$collection_id}", false);
        if($pre_form_state) {
            $display_name  = $pre_form_state['values']['display_name'];
            $copyright_op  = $pre_form_state['values']['copyright'];
            $note          = $pre_form_state['values']['note'];
            $openmosaic    = $pre_form_state['values']['openmosaic'];
            $online        = 1; // 線上指認
            $hd = 1;
            if($copyright_op == 8) $hd = 0; // fair use
            $claim_id = _collclaim_add_claim($uid, $collection_id, $hd, $copyright_op, $display_name, $note, $openmosaic, $online); // write the claim to DB
            if($claim_id == 0) {
              drupal_set_message('_online_collection_claim_submit(): Fail to add claim.');
              drupal_goto("/$collection_id"); // add fail, go back to that collection
            }

            // verify it directly
            //_collclaim_add_onlineclaim($claim_id);
            //drupal_goto("/$collection_id");
            drupal_set_message('您已完成線上指認，等管理員確認過後即可完成指認。');
            drupal_goto('/user_identified_collections');
        } else {
            drupal_set_message('請依照正確的步驟進行指認。');
            drupal_goto("collection/identify/{$collection_id}");    
        }

    } else {
        drupal_set_message('請輸入正確的姓名。');
        drupal_goto("collection/identify/{$collection_id}");
    }
}

function _collection_claim_statement($form_state) {

    global $user;

    $copyright_db = array(
        0 => t('CC0 1.0 公眾領域貢獻宣告 (Public Domain Dedication)'),
        1 => t('創用 CC 「姓名標示 3.0 台灣」授權條款及其後版本 (CC BY 3.0 TW and later)'),
        2 => t('創用 CC 「姓名標示-相同方式分享 3.0 台灣」授權條款及其後版本 (CC BY-SA 3.0 TW and later)'),
        3 => t('創創用 CC 「姓名標示-非商業性 3.0 台灣」授權條款及其後版本 (CC BY-NC 3.0 TW and later)'),
        4 => t('創用 CC 「姓名標示-禁止改作 3.0 台灣」授權條款及其後版本 (CC BY-ND 3.0 TW and later)'),
        5 => t('創用 CC 「姓名標示-非商業性-相同方式分享 3.0 台灣」授權條款及其後版本 (CC BY-NC-SA 3.0 TW and later)'),
        6 => t('創用 CC 「姓名標示-非商業性-禁止改作 3.0 台灣」授權條款及其後版本 (CC BY-NC-ND 3.0 TW and later)'),
        7 => t('本藏品僅供他人在合理使用範圍內使用 (Fair Use)')
    );
    
    $uid           = $user->uid;
    $collection_id = $form_state['values']['collection_id'];
    $display_name  = $form_state['values']['display_name'];
    $copyright_op  = $form_state['values']['copyright'];
    $copyright     = $copyright_db[intval($copyright_op)];
    $note          = $form_state['values']['note'];
    $openmosaic    = $form_state['values']['openmosaic'];
    $author = _collclaim_get_author($uid);

    $email        = $author['email'];
    $phone        = $author['phone'];
    $address      = $author['address'];
    $real_name    = $author['real_name'];
    $four_digitid = $author['4digitid'];
    

    $statement = <<<STATEMENT
    <div id='claim_id'>$claim_id</div>
    <div id="statement">
      <h2>318 公民運動文物紀錄典藏庫</h2>
      <h2>藏品著作權利聲明及同意書</h2>
  
      <ol type="I">
        <li><h3>聲明及同意事項</h3>
          <ol type="i">
            <li><p>僅以下列表格之填寫、列印、親筆簽名及書面交寄之流程，向 318 公民運動文物紀錄典藏庫(以下稱典藏庫)聲明收錄藏品之權利狀態，以利典藏庫於藏品展示時為本人標註適當之顯名聲明。此外，典藏庫並得就本聲明書內之指定藏品，依本人所選定之公眾授權及宣告方式，釋出高解析度之數位重製品，提供公眾使用。</p>
            </li>
            <li><p>透過表格資訊之填寫，代表本人明確知悉並同意，若以己名或冒名聲明非本人創作之藏品及其權利，須自負法律責任，且不得將相關責任以任何理由歸諸於典藏庫或其他相信此權利聲明資訊之善意第三人。</p>
            </li>
            <li><p>本人同意典藏庫於收受藏品著作權利聲明及同意書後，可對本人聲明擁有著作權利之藏品，進行下列彈性權宜處置：</p>
               <ol type="1">
                  <li><p>就本人選定為對公眾授權之藏品，典藏庫得依資源負載以及風險評估之規劃，自主決定是否以高解析度之數位重製品為該藏品進行釋出。</p>
                  </li>
                  <li><p>典藏庫僅對本聲明權利流程進行步驟控管，並不對聲明資訊作實質審驗。當個別藏品有不同權利主體進行複式聲明時，典藏庫或將以揭露併存的方式處理；倘若個別聲明人對其主張藏品權利狀態有釐清之需求，典藏庫當徵詢相關人並獲其聯絡資訊披露之同意後，橋接雙方、多方聲明人自行對權利狀態進行協商處理。</p>
                  </li>
                  <li><p>本人聲明之藏品或可能因為涉及個人資料或其他因素，以致部份影像內容已被遮蔽。本人同意典藏庫目前的處理方式，以及後續的任何處理方式，如回復被遮蔽的內容，或維持、新增遮蔽處理。</p>
                  </li>
               </ol>
            </li>
          </ol>
        </li>
        <li> <h3>著作品資訊及其權利聲明與授權行使</h3>
          <p>本人聲明以下藏品為本人所創作，擁有著作權利並同意行使以下授權及宣告方式。對採行公眾授權方式或公眾領域貢獻宣告的創作，本人並同意典藏庫得釋出高解析度之數位重製品，提供公眾使用（* 必填資訊）：
          </p>
          <ol type="i">
            <li>
               藏品識別號： $collection_id
            </li>
            <li>
               聲明人本名*： $real_name
            </li>
            <li>
               自訂姓名標示方式（於展示該藏品時標示使用）： $display_name
            </li>
            <li>
               對該聲明藏品所行使的授權方式*： $copyright
            </li>
            <li>
               身分證字號後四碼： $four_digitid
            </li>
            <li>
               連絡電話： $phone
            </li>
            <li>
               電子郵件*： $email
            </li>
            <li>
               通訊地址*： $address
            </li>
          </ol>
          <p>除姓名標示外，以上個人資料僅為身分識別及供典藏庫洽簽署人確認資訊之用，不對外揭露。</p>
        </li>
        <li><h3>簽章及具結</h3>
            <p>僅以下列親筆署名或蓋章，表達本聲明及同意書所填寫之資料係為真實。</p>
            <p>&#160;</p>
            <p>立聲明及同意書人：<span class="name to_fill">&#160;</span>&#160;(親筆簽名/蓋章)</p>
            <p>日期：公元&#160;<span class="year to_fill">&#160;</span>&#160;年&#160;<span class="month to_fill">&#160;</span>&#160;月&#160;<span class="day to_fill">&#160;</span>&#160;日
        </li>
      </ol>
    </div>
STATEMENT;
    return $statement;  
}

