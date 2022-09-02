Tantissimi esempi di test sono sul repo git di laravel/laravel

Questo metodo lo creo per gestire la auth:
- setUp() è il costruttore nelle classi di Test di PHPunit
```aidl
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->admin = $this->createUser(isAdmin: true);
    }
```
Così nelle response posso usare actingAs(): 
```aidl
    $response = $this->actingAs($this->user)->get('/products');
```

Posso filtrare i test per classe o per metodo specifico
```aidl
    php artisan test --filter=AuthTest
    php artisan test --filter=test_api_product_invalid_store_returns_error
```    

Online c'è un altro branch con i file per i test con PEST
- credo non si possano mettere PEST e UNIT nello stesso proj perchè vanno in conflitto probabilmente
