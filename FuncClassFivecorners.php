<?
/*

//Инфоблоки
getIdIblockByCode ($code) - Получение ID инфоблока по его символьному коду
getIdSectionByCode ($code, $iblock) - Получение ID раздела по его символьному коду
getIdSections ($iblock, $active) - Получение ID всех разделов
getIdElements($iblock, $section, $active) - Получение ID всех элементов
getSectionsTree($iblock, $active) - Получение разделов инфоблока в виде дерева

//Пользователь
getUserFieldValue($field, $id) - Получение значения пользовательского поля пользователя

//Заказы
getTotalAmountUser($userId) - Получение общей потраченной суммы по всем заказам пользователя
getCountOrders($userId, $statusId) - Получить количество заказов пользователя, в заданном статусе

//Шифрование
mc_encrypt($encrypt, $key) - шифрование текста
mc_decrypt($decrypt, $key) - дешифрование текста

//Регулярные выражения
pregGetDate($string) - Метод возвращает первую дату из текста в формате DD.MM.YYYY
pregClearUrl($string) - Метод очищает url от GET параметров

 */



class FuncClassFivecorners
{

  function __construct()
  {
    CModule::IncludeModule('iblock');
    CModule::IncludeModule('sale');
  }

  /*
  Получение ID инфоблока по его символьному коду

  @param string $code - код инфоблока
  @return string - ID инфоблока
   */
  public function getIdIblockByCode($code)
  {
    $id = 0;
    $res = CIBlock::GetList(
        array(),
        array(
          'SITE_ID'=>SITE_ID,
          "CODE"=>$code
        ), false
    );
    if($ar_res = $res->Fetch()) {
      $id = $ar_res['ID'];
    };

    return $id;
  }

  /*
  Получение ID раздела по его символьному коду

  @param string $code - код раздела
  @param string $iblock - id или код инфоблока
  @return string - ID раздела
   */
  public function getIdSectionByCode($code, $iblock = false)
  {
    $id = 0;
    if ($iblock) {
      if ((int)$iblock) {
        $arFilter = array('IBLOCK_ID' => $iblock, 'CODE' => $code);
      } else {
        $arFilter = array('IBLOCK_CODE' => $iblock, 'CODE' => $code);
      };
    } else {
      $arFilter = array('CODE' => $code);
    };

    $rsSections = CIBlockSection::GetList(array(), $arFilter, false, array('ID'));
    if ($arSection = $rsSections->Fetch()) {
      $id = $arSection['ID'];
    };

    return $id;
  }

  /*
  Получение ID всех разделов

  @param string $iblock - id или код инфоблока
  @param string 'Y' $active - выбор только активных разделов, по умолчанию все
  @return array string - массив ID разделов
   */
  public function getIdSections($iblock, $active = '')
  {
    if ((int)$iblock) {
      $arFilter = array('IBLOCK_ID' => $iblock, 'ACTIVE' => $active);
    } else {
      $arFilter = array('IBLOCK_CODE' => $iblock, 'ACTIVE' => $active);
    };

    $rsSections = CIBlockSection::GetList(array(), $arFilter, false, array('ID'));
    $arIds = array();
    while($arSection = $rsSections->GetNext()) {
        $arIds[] = $arSection['ID'];
    }
    return $arIds;
  }

  /*
  Получение ID всех элементов

  @param string $iblock - id или код инфоблока
  @param string $section - id или код раздела
  @param string 'Y' $active - выбор только активных элементов, по умолчанию все
  @return array string - массив ID элементов
   */
  public function getIdElements($iblock, $section = false , $active = '')
  {
    if ((int)$iblock) {
      if ($section) {
        if ((int)$section) {
          $arFilter = array('IBLOCK_ID' => $iblock, "SECTION_ID" => $section, 'ACTIVE' => $active);
        } else {
          $arFilter = array('IBLOCK_ID' => $iblock, "SECTION_CODE" => $section, 'ACTIVE' => $active);
        }
      } else {
        $arFilter = array('IBLOCK_ID' => $iblock, 'ACTIVE' => $active);
      };
    } else {
      if ($section) {
        if ((int)$section) {
          $arFilter = array('IBLOCK_CODE' => $iblock, "SECTION_ID" => $section, 'ACTIVE' => $active);
        } else {
          $arFilter = array('IBLOCK_CODE' => $iblock, "SECTION_CODE" => $section, 'ACTIVE' => $active);
        }
      } else {
        $arFilter = array('IBLOCK_CODE' => $iblock, 'ACTIVE' => $active);
      };
    };

    $arIds = array();
    $arSelect = Array("ID", "IBLOCK_ID");
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    while($ar_fields = $res->GetNext()){
      $arIds[] = $ar_fields["ID"];
    }
    return $arIds;
  }

  /*
  Получение разделов инфоблока в виде дерева

  @param string $iblock - id или код инфоблока
  @param string 'Y' $active - выбор только активных разделов, по умолчанию все
  @return array
   */
  public function getSectionsTree($iblock, $active = '')
  {
    if ((int)$iblock) {
      $arFilter = array('IBLOCK_ID' => $iblock, 'ACTIVE' => $active);
    } else {
      $arFilter = array('IBLOCK_CODE' => $iblock, 'ACTIVE' => $active);
    };

    $arSelect = array('IBLOCK_ID','ID','NAME','CODE','DEPTH_LEVEL','IBLOCK_SECTION_ID');
    $arOrder = array('DEPTH_LEVEL'=>'ASC','SORT'=>'ASC');
    $rsSections = CIBlockSection::GetList($arOrder, $arFilter, false, $arSelect);
    $sectionsLinc = array();
    $arResult['ROOT'] = array();
    $sectionsLinc[0] = &$arResult['ROOT'];
    while($arSection = $rsSections->GetNext()) {
        $sectionsLinc[intval($arSection['IBLOCK_SECTION_ID'])]['CHILD'][$arSection['ID']] = $arSection;
        $sectionsLinc[$arSection['ID']] = &$sectionsLinc[intval($arSection['IBLOCK_SECTION_ID'])]['CHILD'][$arSection['ID']];
    };

    unset($sectionsLinc);
    return $arResult['ROOT'];
  }

  /*
  Получение значения пользовательского поля пользователя

  @param string $field - код поля
  @param string $id - ID пользователя
  @return mixed - возращает значение поля или id элемента выбора
   */
  public function getUserFieldValue($field, $id) {
    $filter = array("ID" => $id);
    $rsUsers = CUser::GetList(($by = "NAME"), ($order = "desc"), $filter, array('SELECT' => array($field), 'FIELDS' => array('ID')));
    while ($arUser = $rsUsers->Fetch()) {
      return $arUser[$field];
    }
  }

  /*
  Получение общей потраченной суммы по всем заказам пользователя

  @param string $userId - ID пользователя
  @return mixed - общая сумма
  */
  public function getTotalAmountUser($userId)
  {
    $calculator = new \Bitrix\Sale\Discount\CumulativeCalculator($userId, SITE_ID);
    return $calculator->calculate(); //Сумма заказов пользователя
  }

  /*
  Получить количество заказов пользователя, в заданном статусе

  @param string $userId - ID пользователя
  @param string|array $statusId - ID статуса заказа (N, F, D и т.п.)
  @return string - количество заказов
  */
  public function getCountOrders($userId, $statusId)
  {
    $arFilter = array("USER_ID" => $userId, "STATUS_ID" => $statusId);
    $countComplOrders = CSaleOrder::GetList(array("DATE_INSERT" => "ASC"), $arFilter, array());
    return $countComplOrders;
  }




  /* Очень надежное обратимое шифрование
  Использует библиотеку Mcrypt
  Если ее нет: установить командой apt-get install php5-mcrypt для Debian-подобных систем (в т.ч. Mint, Ubuntu)
  или yum install php-mcrypt для RedHat-подобных систем (в т.ч. Fedora, openSUSE, CentOS)
  или любым другим способом, который вам нравится (через dpkg, rpm, yast и т.д.).

  @param string $key - общий шестандцатиричный ключ длиной 32 или 64 символа.
  */
  // Encrypt Function
  /*
  @param string $encrypt - шифруемая строка
   */
  public function mc_encrypt($encrypt, $key)
  {
    $encrypt = serialize($encrypt);
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
    $key = pack('H*', $key);
    $mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
    $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);
    $encoded = base64_encode($passcrypt).'|'.base64_encode($iv);
    return $encoded;
  }

  // Decrypt Function
  /*
  @param string $decrypt - дешифруемая строка
  */
  public function mc_decrypt($decrypt, $key)
  {
    $decrypt = explode('|', $decrypt.'|');
    $decoded = base64_decode($decrypt[0]);
    $iv = base64_decode($decrypt[1]);
    if(strlen($iv)!==mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)){ return false; }
    $key = pack('H*', $key);
    $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));
    $mac = substr($decrypted, -64);
    $decrypted = substr($decrypted, 0, -64);
    $calcmac = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));
    if($calcmac!==$mac){ return false; }
    $decrypted = unserialize($decrypted);
    return $decrypted;
  }



  /*Регулярные выражения*/
  /*
  Метод возвращает первую дату из текста в формате DD.MM.YYYY
  */
  public function pregGetDate($string)
  {
    $result = array();
    preg_match('/(0[1-9]|[12][0-9]|3[01])[- \..](0[1-9]|1[012])[- \..](19|20)\d\d/',$string, $result);
    return $result[0];
  }
  /*
  Метод очищает url от GET параметров
  */
  public function pregClearUrl($string)
  {
    $result = array();
    preg_match("/^[a-z0-9-_:.\/]+/",$string,$result);
    return $result[0];
  }



};
?>
