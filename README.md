# MOLPay Gateway Module for X-Cart 5.x

This is a Payment Module for X-Cart, that gives you the ability to process 
payments through MOLPay's Payment Gateway - Hosted.

## Requirements

+ X-Cart 5.3.x (you can get this plugin to work on older 5.2 versions simply by 
changing the **Major Version** to `5.2` in *Main.php*)

## Installation

1. Log into **X-Cart Administration Area** with your Administrator account
1. Navigate to **My addons**
1. Install through click **Upload add-on** and select `.tar` file you downloaded
1. Toggle the **MOLPay - Hosted Integration** plugin from **Off** to **On** and click Save changes
1. Navigate to **Store setup -> Payment methods**
1. Under **Online methods** category, click **Add payment method** and select **MOLPay - Hosted Integration** from the list
1. Enter your credentials and configure the plugin to your liking
1. Go back to **Store setup -> Payment methods** and toggle the **MOLPay - Hosted Integration** payment method from **INACTIVE** to **ACTIVE**

## Installation (Manual)

1. Upload the contents of folder (excluding `README.md`) to the `<root>` folder of your X-Cart installation
1. Log into **X-Cart Administration Area** with your Administrator account
1. Go to **System Settings** -> **Cache Management**, click **Re-deploy the store** and wait until the **Deployment Process** finishes
1. Go to **My addons** -> Locate **MOLPay - Hosted Integration** Module and toggle the **MOLPay - Hosted Integration** plugin from **Off** to **On** and click Save changes
1. Navigate to **Store setup -> Payment methods**
1. Under **Online methods** category, click **Add payment method** and select **MOLPay - Hosted Integration** from the list
1. Enter your credentials and configure the plugin to your liking
1. Go back to **Store setup -> Payment methods** and toggle the **MOLPay - Hosted Integration** payment method from **INACTIVE** to **ACTIVE**

*Note:* If you have trouble with your credentials configuration, get in touch with our [support](support@molpay.com) team

You're now ready to process payments through our gateway.