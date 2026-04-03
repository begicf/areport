 
### Laravel simple Menu from data source

This package is customized for bootstrap 4. *. If you want to change the layout for other css libraries then you need to publish resources (see 'Publish resources' section ).

### Install
composer require begicf/simple-menu

### Register Service Provider
Go in 'config/app.php' file.

Find array **providers** and add this element to register provider:
```
\SimpleMenu\SimpleMenuServiceProvider::class
```

### Migrate 
```
php artisan migrate
```
SQL Menu table

| Name | Type |
| --- | --- |
| id | int |
| parent_id | int |
| title | varchar (255)|
| description | varchar (255)|
| order | int|
| created_at | timestamp|
| update_at | timestamp|


### How to use

Put in view blade template 
```
<x-menu-menu-component type="v" />
```

### Publish resources 
```
php artisan vendor:publish --provider="SimpleMenu\SimpleMenuServiceProvider"

 ```
### Example  
 ``` html
<ul class="navbar-nav mr-auto">
  <x-menu-menu-component type="v" />
</ul>
 ```
##### Image 1
![Image2](https://raw.githubusercontent.com/begicf/simpleMenu/master/images/img_2.png)

##### Image 2
![Image1](https://raw.githubusercontent.com/begicf/simpleMenu/master/images/img_1.png)




