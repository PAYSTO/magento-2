# magento-2

Модуль оплаты через платежный сервис Paysto (http://www.paysto.com)
для Magento 2.3

# Установка модуля

Распакуйте архив в кореневую папку системы Magento 2
(получится путь <MAGENTO_ROOT>/app/code/Paysto/Paysto)

в корневой папке Magento 2 выполните следующие команды

bin/magento cache:flush

bin/magento setup:upgrade

bin/magento setup:di:compile

# Настройка модуля

в панели администрирования Magento 2

Магазины > Конфигурация > ПРОДАЖИ > Методы оплаты > Paysto

# Настройка магазина в кабинете Paysto

установите регистрационные данные магазина из системы Paysto

При регистрации магазина в системе Paysto используйте следующие параметры подключения

Success URL: http://example.com/paysto/result/

Fail URL: http://example.com/paysto/result/
