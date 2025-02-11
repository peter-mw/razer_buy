



products_to_buy

 
    id
    product_id
    product_name
    product_edition
    is_active
    quantity
 






table 

accounts
    id
    name
    email
    password
    otp_seed
    ballance_gold
    ballance_silver
    limit_orders_per_day
    created_at
    updated_at




table

transactions
    id
    account_id
    amount
    product_id
    transaction_date
    transaction_id

     


codes
    id
    account_id
    code
    serial_number
    product_id
    product_name
    product_edition
    buy_date
    buy_value
    created_at
    updated_at

 




===========



Make a command to fetch  all account ballances ayns ync them with the command, and put a sync action in 

@/app/Filament/Resources/AccountResource.php  




===================

in the account table add account_type column to be able to distinguish between the accounts, and add the account_type to the account resource in

@/app/Filament/Resources/AccountResource.php  

also on products_to_buy table add the account_type column to be able to distinguish between the accounts, and add the account_type to the account resource in



==========

ok now remove the limit_orders_per_day fields and change it with limit_amount_per_day, wihhc we will use to calculaye the limit in @/app/Console/Commands/ProcessBuyCommand.php

pud the new field 'limit_amount_per_day' in the db and the model too and remove limit_orders_per_day





===================


on @/app/Filament/Resources/AccountResource.php  add new button callded redeem sivar to gold , and make new command to do that in @/app/Console/Commands/RedeemSilverToGoldCommand.php and cover 1000 siver to 1 gold 





============


@/app/Filament/Resources/AccountResource.php when you rdeem silver to gold and also i nthe command @/app/Console/Commands/RedeemSilverToGoldCommand.php  


pls make it wor hav optional parameter product-id whehere before we reedm in  @/app/Filament/Resources/AccountResource.php  we camn choose the pruct id 

see how the proiduct are selected in @/app/Filament/Resources/ProductToBuyResource.php and use the models to make select for proeuxct id 





=======




Make new resource and table called products where we can add the 

Make Pructs Model and migration and resource and table








id
product_name
product_slug
account_type
product_edition
product_buy_value

duegate_product_name

make the id editable 

then in @/app/Filament/Resources/ProductToBuyResource.php  in the dropdown for product_id use the products_catalog table to select the product_id and product_name and product_edition and product_buy_value





===


in @/app/Models/Product.php and reealted tabase  please add anodhre field called product_face_value als oadd to the resource @/app/Filament/Resources/ProductResource.php  and make it editable and when we make purchare order   with @/app/Filament/Resources/ProductToBuyResource.php   please add ti there too and add to the mdoel  @/app/Models/ProductToBuy.php  and make db migration     

=======



@/app/Filament/    make new rerouce and table and models called "account_ballance_history"


fields:
account_id
ballance_gold
ballance_silver
ballance_update_time




where we can see the ballance sitry , and when updateting the ballance in the table

also, when calloing @/app/Console/Commands/SyncAccountBalancesCommand.php  add the prevous ballance in the hisyory




============


@/app/Jobs/ProcessBuyJob.php pls make the saving on the new format that is from the service    




===============


on @/app/Filament/Resources/AccountResource.php  new column that hs is_active for the account , also add in @/app/Models/Account.php and the database , also when proiceesing jobs in @/app/Console/Commands/ProcessBuyCommand.php  sync only the is_active accounts 

=============





in @/app/Filament/Resources/PurchaseOrderResource.php add optional field callec account_id, makei t a select to get the account, but iptional 

add the field to the @/app/Models/PurchaseOrders.php and the database and the migration

  (whnbe account_id is set for the order, then use it in @app/Console/Commands/ProcessBuyCommand.php and @app/Jobs/ProcessBuyJob.php  to process only the orders for the account_id 





============

Please add new table and resouce called "SystemLog" and record all calls and parameters from @/app/Services/RazerService.php  in th e table



it must have 

"id"
"source"
"params"
"response"
"created_at"
"status"



Also make filament resource for it and make it editable and add to the @/app/Services/RazerService.php  to save the logs in the table   

