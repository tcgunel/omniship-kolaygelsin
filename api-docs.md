# Kolay Gelsin Entegrasyon Dokümanı

## İÇİNDEKİLER
- [DOKÜMANIN AMACI](#dokümanın-amacı)
- [SERVİS YAPISI](#servis-yapısı)
- [MESAJ YAPISI](#mesaj-yapısı)
- [ÖRNEK SERVİS ÇAĞRISI](#örnek-servis-çağrısı)
- [SERVİS METODLARI](#servis-metodları)
    - [ALICI KAYDETME](#alıcı-kaydetme)
    - [GÖNDERİ KAYDETME](#gönderi-kaydetme)
    - [GÖNDERİ KAYIT V.2](#gönderi-kayıt-v2)
    - [GÖNDERİ İPTAL ETME](#gönderi-iptal-etme)
    - [WEBHOOK](#webhook)
    - [GÖNDERİ DURUM / TARİHÇE SORGULAMA](#gönderi-durum--tarihçe-sorgulama)
    - [SİZE TANIMLANMIŞ OLASI GÖNDERİ DURUMLARI](#size-tanımlanmış-olası-gönderi-durumları)
    - [GÖNDERİYE AİT DÖKÜMANIN ÇEKİLMESİ](#gönderiye-ait-dökümanın-çekilmesi)
    - [TESLİM EDİLMİŞ GÖNDERİ İÇİN İADE GİRİLMESİ](#teslim-edilmiş-gönderi-için-iade-girilmesi)

## DOKÜMANIN AMACI
Kolay Gelsin sistemi gönderi kayıt, durum sorgulama ve iptal servis detaylarını içeren dokümandır.

## DOKÜMANIN TARİHÇESİ

| Versiyon No | Güncelleme Tarihi | Güncelleyen | Açıklama |
|-------------|-------------------|-------------|----------|
| V00         | 01.01.2019        |             | Dokümanın Oluşturulması |

## SERVİS YAPISI
Sunulan servis REST bir servistir ve OAuth2 token ile güvenliği sağlanmıştır. Tüm servis istekleri ve cevapları JSON formatındadır ve tüm REST servis çağrıları POST edilerek gönderilecektir. Post isteğinin gönderileceği URL test ortamı için aşağıdaki şekilde oluşmaktadır.

REST API url’i => https://apibeta.klyglsn.com/api/request/{{MethodAdı}} şeklindedir.

## MESAJ YAPISI
Servis istekleri JSON formatında ve servis metoduna özgü olacaktır. Ancak tüm servis cevapları aşağıda belirtilen şekilde ortak bir yapıda dönülmektedir.

```json
{
  "Source": "parcelDelivery",
  "Target": "APIReadQ",
  "Intent": null,
  "Tag": null,
  "JobOwner": "APIReadQ",
  "JobId": "S-KGTEST-2018-03-21-11-48-50-69885.12",
  "Payload": {
    // Burada her servis metoduna özgü bilgiler dönülecektir
  },
  "Username": "customerUser",
  "ResultCode": 200,
  "ResultMessage": "OK"
}
```

Yukarıda belirtilen servis cevabı API çağrısı sonucu başarılı ise HTTP Status 200 koduyla dönülecektir. Sistemsel ya da iş kuralları gereği hata dönülecekse "ResultCode" ve "ResultMessage" parametreler ile hata kodu ve hata mesajı dönülmektedir. Bu bağlamda, dönülen cevap mesajlarında sizin cevap olarak değerlendireceğiniz alan sadece Payload, ResultCode ve ResultMessage alanları olacaktır. Diğer alanlar, loglanacak ve sorun yaşanması durumunda bizimle paylaşılarak hatanın tespitini kolaylaştıracak bilgilerdir.

Tüm ortamlar için gerekli Token, Kolay Gelsin müşteri numaranız ve Adres ID niz daha sonra sizlerle paylaşılacaktır.

## ÖRNEK SERVİS ÇAĞRISI
Postman üzerinden yapılan örnek sorgulama servis çağrısı aşağıdaki gibidir.
- Authorization, Headers tabına token ile birlikte eklenmelidir.
- Content-Type header’ı ise application/json olmalıdır.
- Body kısmına servise göndermek istediğimiz JSON ı eklemelidir.

## SERVİS METODLARI

### ALICI KAYDETME
İlk işlem olarak gönderinin alıcısını kaydetmek gerekmektedir. Bu işlem sonucunda dönülen RecipientId sinin, gönderi kayıt aşamasıda RecipientId olarak gönderilmesi beklenmektedir.

https://apibeta.klyglsn.com/api/request/SaveRecipient adresine aşağıdaki parametreler ile post isteğinden bulunulmalıdır.

#### Parametreler (REQUEST)

| Parametre                  | Tip     | Açıklama |
|----------------------------|---------|----------|
| RecipientType              | int     | Alıcı Tipi (1-Bireysel, 2-Kurumsal) |
| RecipientIdentityNumber    | string  | Gönderim yapılacak alıcı bireysel müşteri ise TC Kimlik numarası |
| RecipientTaxIdentityNumber | string  | Gönderim yapılacak alıcı kurumsal müşteri ise Vergi Kimlik numarası |
| RecipientTitle*            | string  | Gönderim yapılacak alıcı (kurumsal ise zorunlu) ünvan bilgisi |
| RecipientName*             | string  | Gönderim yapılacak alıcı (bireysel ise zorunlu) adı |
| RecipientSurname*          | string  | Gönderim yapılacak alıcı (bireysel ise zorunlu) soyadı |
| Email                      | string  | Gönderim yapılacak alıcının email adresi |
| Gsm                        | string  | Gönderim yapılacak alıcının GSM numarası |
| OnlyDeliverToRecipient     | boolean | Eğer gönderinin sadece alıcıya teslim edilmesi gerekiyorsa bu değer true gönderilmelidir. |
| SaveOutOfCoverage          | boolean | Alıcı adresinin Kolay Gelsin çalışma alanının dışında olması durumunda alıcı bilgilerinin kaydedilip kaydedilmeyeceğini belirler (True= Kaydet, False= Kaydetme) |
| Address                    | Address | Aşağıda parametreleri yer alan Address nesnesi. |
| Address.AddressTypeId      | int     | Alıcı adresinin tipi (1-Ev adresi, 2-İş adresi) |
| Address.AddressText*       | string  | Alıcı adresinin metni |
| Address.PostalCode         | string  | Alıcı adresi posta kodu |
| Address.CityId*            | int     | Alıcı adresi il id’si. İl plakaları olarak gönderilecektir. |
| Address.TownName           | string  | Alıcı adresi ilçe ismi |
| Address.BuildingNumber     | string  | Alıcı adresi bina numarası |
| Address.BuildingName       | string  | Alıcı adresi bina adı |
| Address.FloorNumber        | string  | Alıcı adresi kat numarası |
| Address.DoorNumber         | string  | Alıcı adresi kapı numarası |
| Address.CompanyTitle       | string  | Alıcı adresi iş adresi ise, Şirket ünvanı |
| Address.CompanyDepartment  | string  | Alıcı adresi iş adresi ise, Şirket Departmanı |
| Address.Direction          | string  | Alıcı adresi tarifi |

#### RESPONSE

| Parametre    | Tip | Açıklama |
|--------------|-----|----------|
| RecipientId  | int | SaveIntegrationShipment metodunda RecipientId alanında gönderilecek gönderinin alıcı ID’si. |

(*) Zorunlu alanlar

#### Kurumsal alıcı için örnek SaveRecipient isteği
```json
{
  "RecipientType": 2,
  "Address": {
    "CityId": 34,
    "TownName": "Sancaktepe",
    "AddressTypeId": 2,
    "AddressText": "Ekol Lojistik İmam Hatip Cd. Eyüp Sultan Mh. Sancaktepe İstanbul",
    "BuildingNumber": "1",
    "FloorNumber": "1",
    "DoorNumber": "1",
    "BuildingName": "Ekol",
    "PostalCode": "34500",
    "CompanyTitle": "Ekol Lojistik",
    "CompanyDepartment": "Kargo",
    "Direction": "Ekol lojistik lavinya tesisi samandıra"
  },
  "RecipientTaxIdentityNumber": "9240913225",
  "Email": "ekol@ekol.com",
  "RecipientTitle": "Ekol Lojistik AŞ",
  "Gsm": "1234567890",
  "OnlyDeliverToRecipient": false
}
```

#### Bireysel alıcı için örnek SaveRecipient isteği
```json
{
  "RecipientType": 1,
  "Address": {
    "CityId": 34,
    "TownName": "Kadıköy",
    "AddressTypeId": 1,
    "AddressText": "Mehmet Akman Sk. Koşuyolu Mh. Kadıköy İstanbul",
    "BuildingNumber": "1",
    "FloorNumber": "1",
    "DoorNumber": "1",
    "BuildingName": "Apartman",
    "PostalCode": "34500",
    "Direction": "adres tarifi"
  },
  "Email": "bireysel@musteri.com",
  "RecipientIdentityNumber": "38203873510",
  "RecipientName": "Bireysel",
  "RecipientSurname": "Müşteri",
  "Gsm": "1234567890",
  "OnlyDeliverToRecipient": false
}
```

#### Hatalı İşlem Tetiklenmesi Sonucunda Dönen Response Bilgileri
- Alıcı kayıt metodu içerisinde iletilen; SaveOutOfCoverage değeri false iken AddressText alanında gönderilen adres, KG’nin Hizmet alanı dışında ise:  
  "ResultCode": 500.0, "ResultMessage": "Bu kordinatta kurye çalışma alanı bulunamamıştır."
- Bireysel alıcı kaydı için; CityId, TownName, AddressText ve Kurumsal alıcı için; CityId, TownName, BuildingName, AddressText alanlarındaki değerlerin metod içerisinde gönderilmediği durumda:  
  "ResultCode": 500.0, "ResultMessage": "ErrorSavingReceiver"
- Bireysel Alıcı kayıt metodu içerisinde; OnlyDeliverToRecipient değeri true iken RecipientIdentityNumber alanı iletilmediği durumda:  
  "ResultCode": 500.0, "ResultMessage": "IdentityNumberEntryIsRequired"
- Bireysel Alıcı kayıt metodu içerisinde; OnlyDeliverToRecipient değeri true iken iletilen RecipientIdentityNumber değerinin tckn regex formatına uygun olmaması durumunda:  
  "ResultCode": 500.0, "ResultMessage": "IdentityNumberNotValid"
- Kurumsal Alıcı kayıt metodu içerisinde; OnlyDeliverToRecipient değeri true iken RecipientIdentityNumber alanı iletilmediği durumda:  
  "ResultCode": 500.0, "ResultMessage": "TaxIdentificationNumberInputIsRequired"
- Kurumsal Alıcı kayıt metodu içerisinde; OnlyDeliverToRecipient değeri true iken iletilen RecipientTaxIdentityNumber değerinin vkn regex formatına uygun olmaması durumunda:  
  "ResultCode": 500.0, "ResultMessage": "TaxIdentificationNumberNotValid"
- Kurumsal Alıcı kayıt metodu içerisinde; OnlyDeliverToRecipient değeri false iken iletilen RecipientTaxIdentityNumber değerinin rakam dışı karakter içermesi durumunda:  
  "ResultCode": 500.0, "ResultMessage": "Alici vergi kimlik numarasinda gecersiz karakterler bulunuyor"
- Bireysel Alıcı kayıt metodu içerisinde; OnlyDeliverToRecipient değeri false iken iletilen RecipientIdentityNumber değerinin rakam dışı karakter içermesi durumunda:  
  "ResultCode": 500.0, "ResultMessage": "Alici kimlik numarasinda gecersiz karakterler bulunuyor"
- Alıcı Kayıt Metodu içerisinde iletilen CityId değerinin; KG sisteminde karşılık gelen bir il bulunmaması durumunda:  
  "ResultCode": 500.0, "ResultMessage": "CityNotFound"
- Alıcı Kayıt Metodu içerisinde iletilen AddressText ile konum hesaplaması için dış kaynaktan başarılı dönüt alınmadığı durumda:  
  "ResultCode": 500.0, "ResultMessage": "GeoCodeFail"

### GÖNDERİ KAYDETME
Gönderinin bilgilerinin Kolay Gelsin sistemine kaydedileceği servistir.

https://apibeta.klyglsn.com/api/request/SaveIntegrationShipment adresine aşağıdaki parametreler ile post isteğinden bulunulmalıdır.

#### Parametreler (REQUEST)

| Parametre                  | Tip                | Açıklama |
|----------------------------|--------------------|----------|
| SenderCustomer*            | SenderCustomer     | Gönderen bilgilerini içeren nesne |
| SenderCustomer.CustomerId* | int                | Kolay Gelsin tarafından sağlanacak olan gönderen müşteri numarası |
| SenderCustomer.Address*    | Address            | Gönderen müşteri adres bilgilerini içeren nesne |
| SenderCustomer.Address.AddressId* | int         | Kolay Gelsin tarafından sağlanacak olan gönderen müşteri adres Id si |
| Recipient*                 | Recipient          | Alıcı bilgilerini içeren nesne |
| Recipient.RecipientId*     | int                | Öncesinde çağrılan SaveRecipient servis sonucunda dönülen alıcı RecipientId si. Alıcı bilgisinin ve alıcı adresinin aynı olduğundan eminseniz, her gönderi kayıt öncesinde SaveRecipient servisini çağırmak zorunda değilsiniz, önceden edindiğiniz RecipientId değerinin aynısını o alıcının her gönderisi için kullanabilirsiniz. |
| PackageType*               | int                | Taşınacak kargonun paket tipi (1= Dosya, 2=Koli) |
| PayingParty                | int                | Kargo taşıma bedelinin tahsil edileceği taraf (1 = Gönderici, 2 = Alıcı) |
| OnlyDeliverToRecipient     | bool               | Kargo sadece alıcısına mı teslim edilebilir? |
| SenderBrand                | string             | Gönderen marka bilgisi |
| CustomerSpecificCode       | string             | Müşterinin gönderiyi takip etmekte kullanabileceği ve kendisinin belirlediği özel kod |
| ShipmentItemList*          | List<ShipmentItem> | Gönderi içerisinde yer alan parça bilgilerini içeren nesne dizisi |
| ShipmentItem.Width         | decimal            | Gönderi içerisindeki parçanın eni |
| ShipmentItem.Length        | decimal            | Gönderi içerisindeki parçanın boyu |
| ShipmentItem.Height        | decimal            | Gönderi içerisindeki parçanın yüksekliği |
| ShipmentItem.Weight        | decimal            | Gönderi içerisindeki parçanın ağırlığı (Kilogram cinsinden) |
| ShipmentItem.ContentText   | string             | Gönderi parçasının içeriğe dair açıklaması |
| ShipmentItem.CustomerBarcode | string           | Gönderi parçasının müşteri barkodu (Kolay Gelsin barkodu olmak zorunda değildir. Müşteri barkodu ile taşıma yapmayı tercih etmekteyiz.) |
| ShipmentItem.HasCommercialValue | bool        | Gönderi parçasının ticari değeri var mı? |
| ShipmentItem.DeliveryNote  | string             | Gönderi parçasının ticari değeri varsa müşteri irsaliye numarası |
| ShipmentItem.DeliveryNoteDate | DateTime        | Gönderi parçasının ticari değeri varsa müşteri irsaliye tarihi |
| ProductCodeList            | string             | Gönderinin toplandığı gün teslim edilmesi isteniyorsa “SAMEDAY” olarak gönderilir. Onun dışında requeste hiç eklenmeyebilir veya null olarak iletilebilir. |
| ShipmentItem.CustomerOptionalInfo | string    | Müşteri tarafından gerekli görülen şirket özelindeki bilgileridir. Stringify edilmiş JSON,XML ya da düz metin atayabilirsiniz |
| SenderInvoiceAmount        | Decimal            | Tahsilatlı kargo gönderilmek isteniyorsa bu alana ürün bedeli yazılarak iletilebilir. |

#### RESPONSE

| Parametre                | Tip                    | Açıklama |
|--------------------------|------------------------|----------|
| ShipmentId               | string                 | Kaydedilen gönderinin Kolay Gelsin Gönderi Numarası |
| CustomerSpecificCode     | string                 | Müşterinin gönderiyi takip etmekte kullanabileceği ve kendisinin belirlediği özel kod |
| ShipmentTrackingLink     | string                 | Müşterinin gönderiyi takip edebileceği Kolay Gelsin gönderi takip linki |
| ShipmentItemLabelList    | List<ShipmentItemLabel>| Eğer kendi etiketinizi değil de Kolay Gelsin etiketi kullanmak isterseniz her bir gönderi parçası için ayrı üretilmiş etiket bilgileri listesi |
| ShipmentItemLabel.ShipmentItemId | string          | Kaydedilen gönderi parçasının Kolay Gelsin Gönderi Parça Numarası |
| ShipmentItem.CustomerBarcode | string            | Gönderi parçasının müşteri barkodu |
| ShipmentItem.ShipmentItemLabel | string           | Kolay Gelsin etiketi (HTML formatında) |

(*) Zorunlu alanlar

#### Örnek SaveIntegrationShipment 2 parçalı koli gönderi kaydetme isteği
```json
{
  "ShipmentItemList": [
    {
      "Width": 10,
      "Length": 20,
      "Height": 30,
      "Weight": 1,
      "ContentText": "detay",
      "DeliveryNote": "Müşteri İrsaliye Numarası",
      "DeliveryNoteDate": "2018-07-19T00:00:00+03:00",
      "HasCommercialValue": true,
      "CustomerBarcode": "ShpItem2",
      "CustomerTrackingId": "ABC123456"
    },
    {
      "Width": 22,
      "Length": 29,
      "Height": 36,
      "Weight": 1,
      "ContentText": "detay",
      "DeliveryNote": "Müşteri İrsaliye Numarası",
      "DeliveryNoteDate": "2018-07-19T00:00:00+03:00",
      "HasCommercialValue": true,
      "CustomerBarcode": "ShpItem2",
      "CustomerTrackingId": "ABC123457",
      "CustomerOptionalInfo": ""
    }
  ],
  "ProductCodeList": [
    "SAMEDAY"
  ],
  "Recipient": {
    "RecipientId": 45
  },
  "SenderCustomer": {
    "Address": {
      "AddressId": 1
    },
    "CustomerId": 1
  },
  "PayingParty": 1,
  "PackageType": 2,
  "OnlyDeliverToRecipient": true,
  "CustomerSpecificCode": "Shp1",
  "SenderBrand": "SenderBrandCodeExmpl",
  "SenderInvoiceAmount": "20"
}
```

#### Gönderi Kayıt için Hatalı İşlem Tetiklenmesi Sonucunda Dönen Mesaj Response Bilgileri
- Tetiklenen SaveIntegrationShipment içerisinde CustomerId için değer gönderilmediği veya KG sisteminde ilgili CustomerId değerine karşılık gelen bir müşteri bulunmadığı durumda:  
  "ResultCode": 500.0, "ResultMessage": "CustomerNotFound"
- Tetiklenen SaveIntegrationShipment içerisinde AddressId değeri iletilmediği veya iletilen adres müşteriye ait olmadığı durumda:  
  "ResultCode": 404.1, "ResultMessage": "Adres müşteriye ait değil"
- Tetiklenen SaveIntegrationShipment içerisinde RecipientId değeri iletilmediği durumda veya KG sisteminde, iletilen RecipientId değerine karşılık gelen bir alıcı olmaması durumunda:  
  "ResultCode": 500.0, "ResultMessage": "NoContactsFound"
- Tetiklenen SaveIntegrationShipment içerisinde Recipient nesnesi ve/veya Address nesnesi bulunmadığı durumda:  
  "ResultCode": 500.0, "ResultMessage": "Sipariş kaydedilemedi. Hata detayı: Object reference not set to an instance of an object."
- Tetiklenen SaveIntegrationShipment içerisinde SenderCustomer nesnesi ve/veya ShipmentItemList nesnesi bulunmadığı durumda:  
  "ResultCode": 500.0, "ResultMessage": "SomethingWentWrong"
- Tetiklenen SaveIntegrationShipment içerisinde PackageType değeri iletilmediği durumda veya KG sisteminde iletilen PackageType değerine karşılık gelen bir paket tipi olmaması durumunda:  
  "ResultCode": 500.0, "ResultMessage": "MoveOptionNotFound"
- Tetiklenen SaveIntegrationShipment içerisinde SenderInvoiceAmount değeri iletildiği durumda gönderen müşterinin KG sisteminde geçerli bir kontratı yoksa veya geçerli kontratında Tahsilatlı Kargo Gönderilebilir tanımı yapılmamış olduğu durumda:  
  "ResultCode": 500.0, "ResultMessage": "Tahsilatlı kargo yapılamaz."

### GÖNDERİ KAYIT V.2
Gönderinin bilgilerinin Kolay Gelsin sistemine kaydedileceği servistir.

https://apibeta.klyglsn.com/api/request/SaveIntegrationShipmentV2 adresine aşağıdaki parametreler ile post isteğinden bulunulmalıdır.

#### Parametreler (REQUEST)

| Parametre                  | Tip                | Açıklama |
|----------------------------|--------------------|----------|
| SenderCustomer*            | SenderCustomer     | Gönderen bilgilerini içeren nesne |
| SenderCustomer.CustomerId* | int                | Kolay Gelsin tarafından sağlanacak olan gönderen müşteri numarası |
| SenderCustomer.Address*    | Address            | Gönderen müşteri adres bilgilerini içeren nesne |
| SenderCustomer.Address.AddressId* | int         | Kolay Gelsin tarafından sağlanacak olan gönderen müşteri adres Id si |
| Recipient*                 | Recipient          | Alıcı bilgilerini içeren nesne |
| Recipient.RecipientType    | int                | Alıcı Tipi (1-Bireysel, 2-Kurumsal) |
| Recipient.RecipientIdentityNumber | string      | Gönderim yapılacak alıcı bireysel müşteri ise TC Kimlik numarası |
| Recipient.RecipientTaxIdentityNumber | string    | Gönderim yapılacak alıcı kurumsal müşteri ise Vergi Kimlik numarası |
| Recipient.RecipientTitle*  | string             | Gönderim yapılacak alıcı (kurumsal ise zorunlu) ünvan bilgisi |
| Recipient.RecipientName*   | string             | Gönderim yapılacak alıcı (bireysel ise zorunlu) adı |
| Recipient.RecipientSurname* | string            | Gönderim yapılacak alıcı (bireysel ise zorunlu) soyadı |
| Recipient.Email            | string             | Gönderim yapılacak alıcının email adresi |
| Recipient.Gsm              | string             | Gönderim yapılacak alıcının GSM numarası |
| Recipient.OnlyDeliverToRecipient | boolean      | Eğer gönderinin sadece alıcıya teslim edilmesi gerekiyorsa bu değer true gönderilmelidir. |
| Recipient.SaveOutOfCoverage | boolean           | Alıcı adresinin Kolay Gelsin çalışma alanının dışında olması durumunda alıcı bilgilerinin kaydedilip kaydedilmeyeceğini belirler (True= Kaydet, False= Kaydetme) |
| Recipient.Address          | Address            | Aşağıda parametreleri yer alan Address nesnesi. |
| Recipient.Address.AddressTypeId | int           | Alıcı adresinin tipi (1-Ev adresi, 2-İş adresi) |
| Recipient.Address.AddressText* | string         | Alıcı adresinin metni |
| Recipient.Address.PostalCode | string           | Alıcı adresi posta kodu |
| Recipient.Address.CityId*  | int                | Alıcı adresi il id’si. İl plakaları olarak gönderilecektir. |
| Recipient.Address.TownName | string             | Alıcı adresi ilçe ismi |
| Recipient.Address.BuildingNumber | string       | Alıcı adresi bina numarası |
| Recipient.Address.BuildingName | string         | Alıcı adresi bina adı |
| Recipient.Address.FloorNumber | string          | Alıcı adresi kat numarası |
| Recipient.Address.DoorNumber | string           | Alıcı adresi kapı numarası |
| Recipient.Address.CompanyTitle | string         | Alıcı adresi iş adresi ise, Şirket ünvanı |
| Recipient.Address.CompanyDepartment | string    | Alıcı adresi iş adresi ise, Şirket Departmanı |
| Recipient.Address.Direction | string            | Alıcı adresi tarifi |
| PackageType*               | int                | Taşınacak kargonun paket tipi (1= Dosya, 2=Koli) |
| PayingParty                | int                | Kargo taşıma bedelinin tahsil edileceği taraf (1 = Gönderici, 2 = Alıcı) |
| OnlyDeliverToRecipient     | bool               | Kargo sadece alıcısına mı teslim edilebilir? |
| SenderBrand                | string             | Gönderen marka bilgisi |
| CustomerSpecificCode       | string             | Müşterinin gönderiyi takip etmekte kullanabileceği ve kendisinin belirlediği özel kod |
| ShipmentItemList*          | List<ShipmentItem> | Gönderi içerisinde yer alan parça bilgilerini içeren nesne dizisi |
| ShipmentItem.Width         | decimal            | Gönderi içerisindeki parçanın eni |
| ShipmentItem.Length        | decimal            | Gönderi içerisindeki parçanın boyu |
| ShipmentItem.Height        | decimal            | Gönderi içerisindeki parçanın yüksekliği |
| ShipmentItem.Weight        | decimal            | Gönderi içerisindeki parçanın ağırlığı (Kilogram cinsinden) |
| ShipmentItem.ContentText   | string             | Gönderi parçasının içeriğe dair açıklaması |
| ShipmentItem.CustomerBarcode | string           | Gönderi parçasının müşteri barkodu (Kolay Gelsin barkodu olmak zorunda değildir. Müşteri barkodu ile taşıma yapmayı tercih etmekteyiz.) |
| ShipmentItem.HasCommercialValue | bool        | Gönderi parçasının ticari değeri var mı? |
| ShipmentItem.DeliveryNote  | string             | Gönderi parçasının ticari değeri varsa müşteri irsaliye numarası |
| ShipmentItem.DeliveryNoteDate | DateTime        | Gönderi parçasının ticari değeri varsa müşteri irsaliye tarihi |
| ProductCodeList            | string             | Gönderinin toplandığı gün teslim edilmesi isteniyorsa “SAMEDAY” olarak gönderilir. Onun dışında requeste hiç eklenmeyebilir veya null olarak iletilebilir. |
| ShipmentItem.CustomerOptionalInfo | string    | Müşteri tarafından gerekli görülen şirket özelindeki bilgileridir. Stringify edilmiş JSON,XML ya da düz metin atayabilirsiniz |
| SenderInvoiceAmount        | Decimal            | Tahsilatlı kargo gönderilmek isteniyorsa bu alana ürün bedeli yazılarak iletilebilir. |

#### RESPONSE

| Parametre                | Tip                    | Açıklama |
|--------------------------|------------------------|----------|
| ShipmentId               | string                 | Kaydedilen gönderinin Kolay Gelsin Gönderi Numarası |
| CustomerSpecificCode     | string                 | Müşterinin gönderiyi takip etmekte kullanabileceği ve kendisinin belirlediği özel kod |
| ShipmentTrackingLink     | string                 | Müşterinin gönderiyi takip edebileceği Kolay Gelsin gönderi takip linki |
| ShipmentItemLabelList    | List<ShipmentItemLabel>| Eğer kendi etiketinizi değil de Kolay Gelsin etiketi kullanmak isterseniz her bir gönderi parçası için ayrı üretilmiş etiket bilgileri listesi |
| ShipmentItemLabel.ShipmentItemId | string          | Kaydedilen gönderi parçasının Kolay Gelsin Gönderi Parça Numarası |
| ShipmentItem.CustomerBarcode | string            | Gönderi parçasının müşteri barkodu |
| ShipmentItem.ShipmentItemLabel | string           | Kolay Gelsin etiketi (HTML formatında) |

(*) Zorunlu alanlar

#### Örnek SaveIntegrationShipment 2 parçalı koli gönderi kaydetme isteği
```json
{
  "ShipmentItemList": [
    {
      "Width": 10,
      "Length": 20,
      "Height": 30,
      "Weight": 1,
      "ContentText": "Detay",
      "DeliveryNote": "Müşteri İrsaliye Numarası",
      "DeliveryNoteDate": "2018-07-19T00:00:00+03:00",
      "HasCommercialValue": true,
      "CustomerBarcode": "ShpItem1",
      "CustomerTrackingId": "ABC123456",
      "CustomerOptionalInfo": ""
    },
    {
      "Width": 22,
      "Length": 29,
      "Height": 38,
      "Weight": 2,
      "ContentText": "Detay",
      "DeliveryNote": "Müşteri İrsaliye Numarası",
      "DeliveryNoteDate": "2018-07-19T00:00:00+03:00",
      "HasCommercialValue": true,
      "CustomerBarcode": "ShpItem2",
      "CustomerTrackingId": "ABC123457",
      "CustomerOptionalInfo": ""
    }
  ],
  "ProductCodeList": [
    "SAMEDAY"
  ],
  "Recipient": {
    "RecipientType": 1,
    "Address": {
      "CityId": 34,
      "TownName": "Kadıköy",
      "AddressTypeId": 1,
      "AddressText": "Mehmet Akman Sk. Koşuyolu Mh. Kadıköy İstanbul",
      "BuildingNumber": "1",
      "FloorNumber": "1",
      "DoorNumber": "1",
      "BuildingName": "Apartman",
      "PostalCode": "34500",
      "Direction": "adres tarifi"
    },
    "Email": "bireysel@musteri.com",
    "RecipientIdentityNumber": "38203873510",
    "RecipientName": "Bireysel",
    "RecipientSurname": "Müşteri",
    "Gsm": "1234567890",
    "OnlyDeliverToRecipient": false
  },
  "SenderCustomer": {
    "Address": {
      "AddressId": 28
    },
    "CustomerId": 20
  },
  "PayingParty": 1,
  "PackageType": 2,
  "OnlyDeliverToRecipient": true,
  "CustomerSpecificCode": "Shp1",
  "SenderInvoiceAmount": "20"
}
```

#### Gönderi Kayıt V2 için Hatalı İşlem Tetiklenmesi Sonucunda Dönen Mesaj Response Bilgileri
- Tetiklenen SaveIntegrationShipmentv2 içerisinde CustomerId için değer gönderilmediği veya KG sisteminde ilgili CustomerId değerine karşılık gelen bir müşteri bulunmadığı veya SenderCustomer nesnesi iletilmediği durumda:  
  "ResultCode": 500.0, "ResultMessage": "CustomerNotFound"
- Tetiklenen SaveIntegrationShipmentv2 içerisinde AddressId değeri iletilmediği veya iletilen adres müşteriye ait olmadığı durumda:  
  "ResultCode": 404.1, "ResultMessage": "Adres müşteriye ait değil"
- SaveIntegrationShipmentv2 metodu içerisinde; OnlyDeliverToRecipient değeri true ve RecipientType alanı 1 iken RecipientIdentityNumber alanı iletilmediği durumda:  
  "ResultCode": 500.0, "ResultMessage": "IdentityNumberEntryIsRequired"
- Kurumsal Alıcı kayıt metodu içerisinde; OnlyDeliverToRecipient değeri true ve RecipientType alanı 1 iken iletilen RecipientIdentityNumber değerinin tckn regex formatına uygun olmaması durumunda:  
  "ResultCode": 500.0, "ResultMessage": "IdentityNumberNotValid"
- SaveIntegrationShipmentv2 kayıt metodu içerisinde; OnlyDeliverToRecipient değeri true ve RecipientType alanı 2 iken RecipientIdentityNumber alanı iletilmediği durumda:  
  "ResultCode": 500.0, "ResultMessage": "TaxIdentificationNumberInputIsRequired"
- SaveIntegrationShipmentv2 kayıt metodu içerisinde; OnlyDeliverToRecipient değeri true ve RecipientType alanı 2 iken vkn değerinin regex formatına uygun olmaması durumunda:  
  "ResultCode": 500.0, "ResultMessage": "TaxIdentificationNumberNotValid"
- Tetiklenen SaveIntegrationShipmentv2 içerisinde Recipient nesnesi ve/veya Address nesnesi bulunmadığı durumda:  
  "ResultCode": 500.0, "ResultMessage": "ErrorSavingReceiver"
- Tetiklenen SaveIntegrationShipmentv2 içerisinde ShipmentItemList nesnesi bulunmadığı durumda:  
  "ResultCode": 500.0, "ResultMessage": "SomethingWentWrong"
- Tetiklenen SaveIntegrationShipmentv2 içerisinde PackageType değeri iletilmediği durumda veya KG sisteminde, iletilen PackageType değerine karşılık gelen bir paket tipi olmaması durumunda:  
  "ResultCode": 500.0, "ResultMessage": "MoveOptionNotFound"
- SaveIntegrationShipmentv2 metodu içerisinde iletilen; SaveOutOfCoverage değeri false iken AddressText alanında gönderilen adres KG’nin Hizmet alanı dışında ise:  
  "ResultCode": 500.0, "ResultMessage": "Bu kordinatta kurye çalışma alanı bulunamamıştır."
- SaveIntegrationShipmentv2 metodu içerisinde; OnlyDeliverToRecipient değeri false iken iletilen RecipientIdentityNumber değerinin rakam dışı karakter içermesi durumunda:  
  "ResultCode": 500.0, "ResultMessage": "Alici kimlik numarasinda gecersiz karakterler bulunuyor"
- SaveIntegrationShipmentv2 içerisinde iletilen CityId değerinin; KG sisteminde karşılık gelen bir il bulunmaması durumunda:  
  "ResultCode": 500.0, "ResultMessage": "CityNotFound"
- SaveIntegrationShipmentv2 içerisinde iletilen AddressText ile konum hesaplaması için dış kaynaktan başarılı dönüt alınmadığı durumda:  
  "ResultCode": 500.0, "ResultMessage": "GeoCodeFail"
- Tetiklenen SaveIntegrationShipment içerisinde SenderInvoiceAmount değeri iletildiği durumda gönderen müşterinin KG sisteminde geçerli bir kontratı yoksa veya geçerli kontratında Tahsilatlı Kargo Gönderilebilir tanımı yapılmamış olduğu durumda:  
  "ResultCode": 500.0, "ResultMessage": "Tahsilatlı kargo yapılamaz."

### GÖNDERİ İPTAL ETME
Henüz Kolay Gelsin tarafından toplanmamış gönderinin iptalini sağlar. Toplanmış ancak henüz teslim edilmemiş gönderinin teslimat sürecini sonlandırarak iade sürecini başlatır.

https://apibeta.klyglsn.com/api/request/CancelPickupAndDelivery adresine aşağıdaki parametreler ile post isteğinde bulunulmalıdır.

#### Parametreler (REQUEST)

| Parametre                  | Tip    | Açıklama |
|----------------------------|--------|----------|
| ShipmentId*                | string | Kolay Gelsin gönderi numarası. |
| DeliveryCancellationReason* | int   | 1-Standard, 2-Fraud |

(*) Zorunlu alanlar

#### Örnek CancelPickup isteği
```json
{
  "ShipmentId": 9442,
  "DeliveryCancellationReason": 1
}
```

### WEBHOOK
WebHook entegrasyonu, entegrasyon süreçlerini kolaylaştırmak ve hızlandırmak için hazırlanmıştır.

Sizin tarafınızdan yazılacak bir servise aşağıda belirtilen formda bir json POST edilerek gönderi üzerinde alınan tüm aksiyonlardan anında haberdar edilmeniz sağlanabilecektir. Yazdığınız servisin aşağıda paylaşılan JSON datasını kabul etmesi gerekmektedir. Sizin tarafınızdan yazılacak bu servis metodunun URL bilgisi Kolay Gelsin ile paylaşılmalıdır. Servisin authorization tipi Basic yada No Auth olacak şekilde yazılmasını beklemekteyiz.

Authentication tipi NoAuth olan bir metot hazırlandı ise hazırlanan bu metotun Kolay Gelsin tarafından çağırıldığının sizin tarafınızdan anlaşılabilmesi adına yine sizin belirleyebileceğiniz bir HASH değeri, HTTP POST çağrısının HEADER kısmına size gönderilmek üzere eklenecektir. Entegrasyonun tamamlanması için metot url ve hash değeri sizin tarafınızdan oluşturup Kolay Gelsin’e iletilmelidir.

Basic Auth tipinde bir metot hazırlandı ise entegrasyonun tamamlanması için metot url, username ve password değerleri sizin tarafınızdan oluşturup Kolay Gelsin’e iletilmelidir.

Kolay Gelsin’in hazırlanan servise erişimi için servisin çalıştığı sunucun 95.0.169.14 IP adresi için yetkilendirilmiş olması gerekmektedir.

#### Parametreler

| Parametre                | Tip              | Açıklama |
|--------------------------|------------------|----------|
| TimeStamp                | DateTime         | Aksiyonun Alındığı Zaman |
| ShipmentId               | string           | Kolay Gelsin Gönderi Numarası |
| ShipmentItemId           | string           | Gönderiye bağlı paketin Kolay Gelsin Id si |
| CustomerTrackingId       | string           | Barkoddan farklı olarak müşterinin pakete özgü takip numarası listesi |
| CustomerBarcode          | string           | Paket üzerindeki müşteri barkodu |
| CustomerSpecificCode     | string           | Gönderiyi temsil eden müşteri kodu |
| CustomerId               | string           | İlgili bildirimin iletildiği müşterinin Kolay Gelsin Sistemindeki müşteri numarası |
| CargoEventType           | CargoEventType   | Gönderi üzerinde alınan kargo aksiyonu (1 - Gönderi KG sisteminde oluşturuldu, 2 - Gönderenden toplama yapacak kurye yola çıktı, 12 - Toplama yapıldı, 13 - Toplama transfer merkezinde gönderi indirildi, 14 - Dağıtım transfer merkezinde gönderi indirildi, 17 - Dağıtım yapacak kuryenin aracına yüklendi, 18 - Dağıtım Kuryesi Transfer Merkezinden ayrıldı, teslimat adresine doğru yola çıktı, 25 - Alıcı adresi hatalı, 26 - Alıcı teslimat adresinde bulunamadı, 28 - Alıcı teslimatı reddetti, 29 - Gönderi teslim edildi, 31 - Gönderen tarafından gönderi durduruldu, gönderene iade edilecek, 32 - Alıcı talebi ile gönderinin iade gönderisi kaydedildi, 33 - Gönderi kayıp, 35 - Alıcı adresi problemli, 39 - Dağıtım görevi ertesi iş gününe devredildi, 46 - Devir depoya alınan gönderinin operasyonel iadesi kaydedildi, 58 - Hasar raporu oluşturuldu) |
| ShipmentPartyType        | string           | İlgili bildirimin iletildi müşterinin gönderinin hangi tarafı olduğu bilgisi (1- Gönderici, 2- Alıcı) |
| Latitude                 | string           | Kullanıcıya gösterilebilecek aksiyon mesajı |
| Longitude                | string           | Aksiyona dair açıklama bilgisi |
| LocationName             | string           | Aksiyonun gerçekleştiği yer |
| NumberPlate              | string           | Gönderinin taşındığı veya yüklenmiş bulunduğu araç plakası |
| CourierName              | string           | Gönderi eğer kuryeye zimmetlenmiş durumda ise kuryenin adı |
| DocumentType             | DocumentType     | Gönderiye üzerinde alınan aksiyona dair bir doküman ilişkisi bulunması durumunda dokümanın tipini belirtir. (4 - Teslim edilmiş gönderinin müşteri imzası, 5 - Müşteri adreste bulunamadığı durumda kapıya yapıştırılıan notun fotoğrafı, 7 - Hasar raporu, 8 - Gönderinin zimmetlendiği kurye fotoğrafı, 9 - Alcıya gönderilen email, 10 - Alıcıya gönderilen sms) |
| DocumentId               | Guid             | Dcoument alanının dolu olması halinde ilişkili olan dokümanın Id si |
| EventDetailModel         | EventDetailModel |          |
| EventDetailModel.DeliveryCancellationReason | string | Gönderinin iptal nedeni (1 - Standart, 2 - Yasal, 3 - Gönderici durdurdu) |
| EventDetailModel.DeliveredPersonRole | string | Gönderinin teslim alan kişinin yakınlık tipi |
| EventDetailModel.DeliveredPersonName | string | Gönderinin teslim edildiği kişinin adı |
| EventDetailModel.PreviousDeliveryDate | string | Planlanan eski teslimat tarihi |
| EventDetailModel.NewDeliveryDate | string | Planlanan yeni teslimat tarihi |
| EventDetailModel.PreviousDeliveryTime | string | Planlanan eski teslimat zaman periyodu |
| EventDetailModel.NewDeliveryTime | string | Planlanan yeni teslimat zaman periyodu |
| EventDetailModel.ReturnShipmentId | string | İade edilecek gönderinin yeni Kolay Gelsin gönderi numarası |
| EventDetailModel.PreviousPrice | string | Ücret değişikliğine neden olacak bir randevu girişine istinaden önceki fiyat ile dolacak alandır. (mevcutta buna neden olacak bir aksiyon bulunmuyor.) |
| CustomerOptionalInfo     | string           | Müşteri tarafından gerekli görülen şirket özelindeki bilgiler. |

#### Servisinize gönderilecek örnek HTTP POST isteği body JSON
```json
{
  "CargoEventType": "1",
  "ShipmentPartyType": "2",
  "ShipmentId": "1",
  "ShipmentItemId": "1",
  "LocationName": "ISTANBUL",
  "NumberPlate": "",
  "CourierName": "",
  "DocumentId": "",
  "DocumentType": "0",
  "Latitude": "",
  "Longitude": "",
  "CustomerBarcode": "",
  "CustomerTrackingId": "1",
  "CustomerOptionalInfo": "",
  "CustomerSpecificCode": "",
  "CustomerId": "29",
  "TimeStamp": "2021-05-13T18:28:01.882+03:00",
  "EventDetailModel": {
    "DeliveredPersonName": "",
    "DeliveredPersonRole": "",
    "DeliveryCancellationReason": "",
    "PreviousDeliveryDate": "",
    "NewDeliveryDate": "",
    "PreviousDeliveryTime": "",
    "NewDeliveryTime": "",
    "PreviousPrice": "",
    "ReturnShipmentId": ""
  }
}
```

### GÖNDERİ DURUM / TARİHÇE SORGULAMA
Gönderinin durumlarının ve üzerlerinde alınan aksiyonlara dair bilgilerinin sorgulanabileceği servistir.

https://apibeta.klyglsn.com/api/request/GetCorporateShipmentsStatus adresine aşağıdaki ShipmentIdList, CustomerSpecificCodeList, CustomerBarcodeList ve CustomerTrackingIdList parametrelerinden en az biri dolu olacak şekilde ve OnlyLatestEvents parametresi belirtilerek post isteğinde bulunulmalıdır.

#### Parametreler (REQUEST)

| Parametre                 | Tip           | Açıklama |
|---------------------------|---------------|----------|
| ShipmentIdList            | List<string>  | Sorgulanmak istenen gönderinin Kolay Gelsin gönderi numarası listesi |
| CustomerSpecificCodeList  | List<string>  | Gönderiyi temsil eden müşteri kodu listesi |
| CustomerBarcodeList       | List<string>  | Paket üzerinden yer alan müşteri barkodu listesi |
| CustomerTrackingIdList    | List<string>  | Barkoddan farklı olarak müşterinin pakete özgü takip numarası listesi |
| OnlyLatestEvents          | bool          | true = Sorgulama yapılmak istenen gönderilerin sadece en son aksiyonları dönülür, false= Sorgulama yapılmak istenen gönderilerin tüm aksiyonları dönülür |

#### RESPONSE

| Parametre                               | Tip                     | Açıklama |
|-----------------------------------------|-------------------------|----------|
| ShipmentModelList                       | List<ShipmentModel>     | Aksiyon listesi dönülecek gönderiye dair tekil bilgileri içerir |
| ShipmentModel.ShipmentId                | string                  | Kolay Gelsin Gönderi Numarası |
| ShipmentModel.ShipmentItemId            | string                  | Gönderiye bağlı paketin Kolay Gelsin Id si |
| ShipmentModel.CustomerTrackingId        | string                  | Barkoddan farklı olarak müşterinin pakete özgü takip numarası listesi |
| ShipmentModel.CustomerBarcode           | string                  | Paket üzerindeki müşteri barkodu |
| ShipmentModel.CustomerSpecificCode      | string                  | Gönderiyi temsil eden müşteri kodu |
| ShipmentModel.CargoEventLogModelList    | List<CargoEventLogModel>|          |
| ShipmentModel.CargoEventLogModel.CargoEventType | CargoEventType  | Gönderi üzerinde alınan kargo aksiyonu (1 - Gönderi Kolay Gelsin sistemine kayıt edildi, 12 - Paket Kolay Gelsin tarafından teslim alındı, 17 - Dağıtım yapacak kuryenin aracına yüklendi, 18 - Dağıtım Kuryesi Transfer Merkezinden ayrıldı, teslimat adresine doğru yola çıktı, 25 - Alıcı adresi hatalı, 26 - Alıcı teslimat adresinde bulunamadı, 28 - Alıcı teslimatı reddetti, 29 - Gönderi teslim edildi, 31 - Gönderen tarafından gönderi durduruldu, gönderene iade edilecek, 32 - Alıcı talebi ile gönderinin iade gönderisi kaydedildi, 33 - Gönderi kayıp, 35 - Alıcı adresi problemli, 39 - Dağıtım görevi ertesi iş gününe devredildi, 46 - Devir depoya alınan gönderinin operasyonel iadesi kaydedildi, 58 - Hasar raporu oluşturuldu) |
| ShipmentModel.CargoEventLogModel.Latitude | string                | Aksiyonun gerçekleştiği enlem |
| ShipmentModel.CargoEventLogModel.Longitude | string               | Aksiyonun gerçekleştiği boylam |
| ShipmentModel.CargoEventLogModel.LocationName | string             | Aksiyonun gerçekleştiği yer |
| ShipmentModel.CargoEventLogModel.NumberPlate | string              | Gönderinin taşındığı veya yüklenmiş bulunduğu araç plakası |
| ShipmentModel.CargoEventLogModel.CourierName | string              | Gönderi eğer kuryeye zimmetlenmiş durumda ise kuryenin adı |
| ShipmentModel.CargoEventLogModel.DocumentType | DocumentType      | Gönderiye üzerinde alınan aksiyona dair bir doküman ilişkisi bulunması durumunda dokümanın tipini belirtir. (4 - Teslim edilmiş gönderinin müşteri imzası, 5 - Müşteri adreste bulunamadığı durumda kapıya yapıştırılıan notun fotoğrafı, 7 - Hasar raporu, 8 - Gönderinin zimmetlendiği kurye fotoğrafı, 9 - Alcıya gönderilen email, 10 - Alıcıya gönderilen sms) |
| ShipmentModel.CargoEventLogModel.DocumentId | Guid                | Dcoument alanının dolu olması halinde ilişkili olan dokümanın Id si |
| ShipmentModel.CargoEventLogModel.EventDetailModel | EventDetailModel |          |
| ShipmentModel.CargoEventLogModel.EventDetailModel.DeliveryCancellationReason | string | Gönderinin iptal nedeni (1 - Standart, 2 - Yasal, 3 - Gönderici durdurdu) |
| ShipmentModel.CargoEventLogModel.EventDetailModel.DeliveredPersonRole | string | Gönderinin teslim alan kişinin yakınlık tipi |
| ShipmentModel.CargoEventLogModel.EventDetailModel.DeliveredPersonName | string | Gönderinin teslim edildiği kişinin adı |
| ShipmentModel.CargoEventLogModel.EventDetailModel.PreviousDeliveryDate | string | Planlanan eski teslimat tarihi |
| ShipmentModel.CargoEventLogModel.EventDetailModel.NewDeliveryDate | string | Planlanan yeni teslimat tarihi |
| ShipmentModel.CargoEventLogModel.EventDetailModel.PreviousDeliveryTime | string | Planlanan eski teslimat zaman periyodu |
| ShipmentModel.CargoEventLogModel.EventDetailModel.NewDeliveryTime | string | Planlanan yeni teslimat zaman periyodu |
| ShipmentModel.CargoEventLogModel.EventDetailModel.ReturnShipmentId | string | İade edilecek gönderinin yeni Kolay Gelsin gönderi numarası |

#### Örnek GetCorporateShipmentsStatus HTTP POST isteği body JSON
```json
{
  "ShipmentIdList": [],
  "CustomerSpecificCodeList": [],
  "CustomerBarcodeList": [],
  "CustomerTrackingIdList": [
    "ABC12345898",
    "ABC12345"
  ],
  "OnlyLatestEvents": true
}
```

#### Tarihçe Sorgulama için Hatalı İşlem Tetiklenmesi Sonucunda Dönen Mesaj Response Bilgileri
- Tetiklenen GetCorporateShipmentsStatus içerisinde ShipmentIdList, CustomerSpecificCodeList, CustomerBarcodeList ve CustomerTrackingIdList için değerlerinin hepsinin boş iletilmesi durumda:  
  "ResultCode": 500.0, "ResultMessage": "Parametrelerin hepsi boş olamaz"
- Tetiklenen GetCorporateShipmentsStatus için gönderici müşteri Webhook tanımı ile KG sistemine entegre olmamış ise:  
  "ResultCode": 500.0, "ResultMessage": "CustomerNotFound"

### SİZE TANIMLANMIŞ OLASI GÖNDERİ DURUMLARI
GetCorporateShipmentsStatus servis cevabında dönülen CargoEventType parametresinin sizin için tanımlanmış olası değerlerinin çekildiği servistir. Sadece bilgi sorgulamak için kullanılabilir. Operasyonel bir gerekliliği yoktur. Ancak operasyonel bir gereklilikle yeni bir statü eklenmesi durumunda ilgili statünün ne anlama geldiğini bu servisi çağırarak öğrenebilirsiniz.

https://apibeta.klyglsn.com/api/request/GetCorporateCustomerCargoEventTypes adresine parametresiz ile post isteğinde bulunulmalıdır.

#### Parametreler (REQUEST)
Yok.

#### RESPONSE

| Parametre                       | Tip                  | Açıklama |
|---------------------------------|----------------------|----------|
| CargoEventTypeList              | List<CargoEventType> | Aksiyon listesi |
| CargoEventType.CargoEventType   | int                  | Aksiyon Id si |
| CargoEventType.EventCustomerDescription | string       | Aksiyonun açıklaması |

#### Örnek GetCorporateCustomerCargoEventTypes HTTP POST isteği body JSON
```json
{}
```

### GÖNDERİYE AİT DÖKÜMANIN ÇEKİLMESİ
GetCorporateShipmentsStatus veya Webhook servislerinde edinebileceğiniz DocumentId si size ulaşan ve gönderiye ait bir doküman varsa ilgili DocumentId ve DocumentType ile dokümanın çekilebileceği servistir.

https://apibeta.klyglsn.com/api/request/GetDocumentForHistory adresine aşağıdaki parametreler ile post isteğinde bulunulmalıdır.

#### Parametreler (REQUEST)

| Parametre     | Tip          | Açıklama |
|---------------|--------------|----------|
| DocumentId*   | Guid         | Çekilmek istenen domanın Id si |
| DocumentType* | DocumentType | Doküman tipi: (4 - Teslim edilmiş gönderinin müşteri imzası, 5 - Müşteri adreste bulunamadığı durumda kapıya yapıştırılıan notun fotoğrafı, 7 - Hasar raporu, 8 - Gönderinin zimmetlendiği kurye fotoğrafı, 9 - Alcıya gönderilen email, 10 - Alıcıya gönderilen sms) |

#### RESPONSE

| Parametre              | Tip          | Açıklama |
|------------------------|--------------|----------|
| DocumentModel          | DocumentModel| Doküman modeli |
| DocumentModel.DocumentId | Guid       | Doküman Id si |
| DocumentModel.Data     | Byte[]       | Doküman PDF formatında ve byte array olarak dönülmektedir. |

(*) Zorunlu alanlar

#### Örnek GetDocumentForHistory HTTP Post isteği body Json
```json
{
  "DocumentId": "C3FFC041-7533-490E-84A7-3B9C87B1K161",
  "DocumentType": 4
}
```

### TESLİM EDİLMİŞ GÖNDERİ İÇİN İADE GİRİLMESİ
Kolay Gelsin ile taşınıp, teslim edilmiş bir gönderinin iadesi oluşturulmak istendiğinde kullanılacak olan servistir.

https://apibeta.klyglsn.com/api/request/SaveIntegrationReturnShipment adresine aşağıdaki parametreler ile post isteğinde bulunulmalıdır.

#### Parametreler (REQUEST)

| Parametre                        | Tip                | Açıklama |
|----------------------------------|--------------------|----------|
| SenderCustomer*                  | SenderCustomer     | Gönderen bilgilerini içeren nesne |
| SenderCustomer.IdentityNumber    | string             | Gönderen bireysel müşteri ise TC Kimlik numarası |
| SenderCustomer.TaxIdentityNumber | string             | Gönderen kurumsal müşteri ise Vergi Kimlik numarası |
| SenderCustomer.CustomerName      | string             | Gönderen müşterinin adı (bireysel müşteri ise zorunlu) |
| SenderCustomer.CustomerSurname   | string             | Gönderen müşterinin soyadı (bireysel müşteri ise zorunlu) |
| SenderCustomer.Title             | string             | Gönderen müşterinin ünvan bilgisi (kurumsal müşteri ise zorunlu) |
| SenderCustomer.CustomerType      | int                | Gönderen Tipi (1- Bireysel, 2- Kurumsal) |
| SenderCustomer.Email             | string             | Gönderen müşterinin email adresi |
| SenderCustomer.Gsm               | string             | Gönderen müşterinin GSM numarası |
| SenderCustomer.PhoneNumber       | string             | Gönderen müşterinin telefon numarası |
| SenderCustomer.DirectCode        | string             | Gönderen müşterinin telefon numarasının dahili kodu |
| SenderCustomer.Address*          | Address            | Gönderen müşteri adres bilgilerini içeren nesne |
| SenderCustomer.Address.AddressTypeId | int            | Gönderen adresinin tipi (1- Ev adresi, 2- İş adresi) |
| SenderCustomer.Address.AddressText* | string          | Gönderen adresinin metni |
| SenderCustomer.Address.PostalCode | string            | Gönderen adresi posta kodu |
| SenderCustomer.Address.CityId*   | int                | Gönderen adresi il id’si. İl plakaları olarak gönderilecektir. |
| SenderCustomer.Address.TownName  | string             | Gönderen adresi ilçe ismi |
| SenderCustomer.Address.BuildingNumber | string        | Gönderen adresi bina numarası |
| SenderCustomer.Address.BuildingName | string          | Gönderen adresi bina adı |
| SenderCustomer.Address.FloorNumber | string           | Gönderen adresi kat numarası |
| SenderCustomer.Address.DoorNumber | string            | Gönderen adresi kapı numarası |
| SenderCustomer.Address.Direction | string             | Gönderen adresi tarifi |
| Recipient                        | Recipient          | Alıcı bilgilerini içeren nesne |
| Recipient.RecipientType          | int                | Alıcı Tipi (1-Bireysel, 2- Kurumsal) |
| Recipient.RecipientIdentityNumber | string            | Gönderim yapılacak alıcının bireysel müşteri ise TC Kimlik numarası |
| Recipient.RecipientTaxIdentityNumber | string        | Gönderim yapılacak alıcının kurumsal müşteri ise Vergi Kimlik numarası |
| Recipient.RecipientTitle         | string             | Gönderim yapılacak alıcının ünvan bilgisi (kurumsal müşteri ise zorunlu) |
| Recipient.RecipientName          | string             | Gönderim yapılacak alıcının adı (bireysel müşteri ise zorunlu) |
| Recipient.RecipientSurname       | string             | Gönderim yapılacak alıcının soyadı (bireysel müşteri ise zorunlu) |
| Recipient.Email                  | string             | Gönderim yapılacak alıcının email adresi |
| Recipient.Gsm                    | string             | Gönderim yapılacak alıcının GSM numarası |
| Recipient.Address                | Address            | Gönderim yapılacak alıcının adres bilgilerini içeren nesne (İletilmediği durumda müşterinin Kolay Gelsin sisteminde kayıtlı olan iade adresi, o da yoksa orjinal gönderinin toplandığı adres baz alınacaktır.) |
| Recipient.Address.AddressTypeId  | int                | Alıcı adresinin tipi (1-Ev adresi, 2-İş adresi) |
| Recipient.Address.AddressText    | string             | Alıcı adresinin metni |
| Recipient.Address.PostalCode     | string             | Alıcı adresi posta kodu |
| Recipient.Address.CityId         | int                | Alıcı adresi il id’si. İl plakaları olarak gönderilecektir. |
| Recipient.Address.TownName       | string             | Alıcı adresi ilçe ismi |
| Recipient.Address.BuildingNumber | string             | Alıcı adresi bina numarası |
| Recipient.Address.BuildingName   | string             | Alıcı adresi bina adı |
| Recipient.Address.FloorNumber    | string             | Alıcı adresi kat numarası |
| Recipient.Address.DoorNumber     | string             | Alıcı adresi kapı numarası |
| Recipient.Address.CompanyTitle   | string             | Alıcı adresi iş adresi ise, Şirket ünvanı |
| Recipient.Address.CompanyDepartment | string          | Alıcı adresi iş adresi ise, Şirket Departmanı |
| Recipient.Address.Direction      | string             | Alıcı adresi tarifi |
| PayerTypeId                      | int                | Kargo taşıma bedelinin tahsil edileceği taraf (1 - Gönderici, 2 - Alıcı) |
| ShipmentId                       | int                | İade edilecek gönderinin Kolay Gelsin gönderi numarası (Gönderi tek parçalı ise; CustomerBarcode veya ShipmentItemId olmadığı durumda zorunlu. Gönderi çok parçalı ise; CustomerBarcode veya ShipmentItemId den biri mutlaka olmalı ve bu durumda ShipmentId zorunlu değil.) |
| ShipmentItemList                 | List<ShipmentItem> | İade edilecek gönderinin parça bilgilerini içeren nesne dizisi |
| ShipmentItem.CustomerBarcode     | string             | Gönderi parçasının müşteri barkodu (Gönderi çok parçalı ise ve ShipmentItemId iletilmeyecekse zorunlu) |
| ShipmentItem.ShipmentItemId      | int                | Gönderi parçasının id’si (Gönderi çok parçalı ise ve CustomerBarcode iletilmeyecekse zorunlu) |

#### RESPONSE

| Parametre                | Tip                    | Açıklama |
|--------------------------|------------------------|----------|
| ShipmentId               | string                 | Kaydedilen gönderinin Kolay Gelsin gönderi numarası |
| CustomerSpecificCode     | string                 | Müşterinin gönderiyi takip etmekte kullanabileceği ve kendisinin belirlediği özel kod |
| ShipmentTrackingLink     | string                 | Müşterinin gönderiyi takip edebileceği Kolay Gelsin gönderi takip linki |
| ShipmentItemLabelList    | List<ShipmentItemLabel>| Eğer kendi etiketinizi değil de Kolay Gelsin etiketi kullanmak isterseniz her bir gönderi parçası için ayrı üretilmiş etiket bilgileri listesi |
| ShipmentItemLabel.ShipmentItemId | string          | Kaydedilen gönderi parçasının Kolay Gelsin Gönderi Parça Numarası |
| ShipmentItem.CustomerBarcode | string            | Gönderi parçasının müşteri barkodu |
| ShipmentItem.ShipmentItemLabel | string           | Kolay Gelsin etiketi (HTML formatında) |

(*) Zorunlu alanlar

#### Örnek SaveIntegrationReturnShipment HTTP Post isteği body Json
```json
{
  "Recipient": {
    "RecipientType": 2,
    "Address": {
      "CityId": 34,
      "TownName": "kadıköy",
      "AddressTypeId": 1,
      "AddressText": "Feneryolu Mah. İrem Sk. Bina No: 7 Kat No: 1 Kapı No: 1 Kadıköy/İstanbul",
      "BuildingNumber": "",
      "FloorNumber": null,
      "DoorNumber": "",
      "BuldingName": null,
      "PostalCode": "",
      "CompanyTitle": null,
      "CompanyDepartment": null,
      "Direction": ""
    }
  },
  "SenderCustomer": {
    "IdentityNumber": "",
    "TaxIdentityNumber": null,
    "CustomerName": "iade",
    "CustomerSurname": "test",
    "Title": null,
    "CustomerType": 1,
    "Email": "",
    "Address": {
      "AddressTypeId": 1,
      "AddressText": "Mimar Sinan mah. Hamam sk. 18/14",
      "Direction": null,
      "BuildingNumber": "1",
      "BuildingName": null,
      "FloorNumber": null,
      "DoorNumber": "1",
      "PostalCode": null,
      "CityId": 34,
      "CityName": "İstanbul",
      "TownName": "üsküdar"
    },
    "Gsm": "",
    "PhoneNumber": "",
    "DirectCode": ""
  },
  "ShipmentId": 0,
  "ShipmentItemList": [
    {
      "CustomerBarcode": "KLY000124563",
      "ShipmentItemId": 0
    },
    {
      "CustomerBarcode": "KLY000124562",
      "ShipmentItemId": 0
    }
  ],
  "PayerTypeId": 1
}
```