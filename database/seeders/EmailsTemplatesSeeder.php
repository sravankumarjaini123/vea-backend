<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailsTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('emails_templates_samples')->insert([
            'name' => 'Forgot Password SYSTEM',
            'description' => '<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">
                                <head>
                                  <meta charset="utf-8" />
                                  <link rel="icon" href="/favicon.ico" />
                                  <meta name="viewport" content="width=device-width, initial-scale=1" />
                                  <!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch><o:AllowPNG/></o:OfficeDocumentSettings></xml><![endif]-->
                                  <meta name="theme-color" content="#000000" />
                                  <link
                                href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Sora:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Hahmlet:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=JetBrains Mono:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Andada Pro:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Epilogue:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Encode Sans:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Manrope:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Lora:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=BioRhyme:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Playfair Display:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Archivo:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Cormorant:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Spectral:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Raleway:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Work Sans:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Lato:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Anton:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Old Standard TT:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Nunito:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Source Sans Pro:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Oxygen:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Open Sans:300,400,500,600,700"
                                rel="stylesheet"
                              />
                                  <title>React App</title>
                                  <style type="text/css">
                                    body {
                                      margin: 0;
                                      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto",
                                        "Oxygen", "Ubuntu", "Cantarell", "Fira Sans", "Droid Sans",
                                        "Helvetica Neue", sans-serif;
                                      -webkit-font-smoothing: antialiased;
                                      -moz-osx-font-smoothing: grayscale;

                                    }
                                    .total-container{
                                        max-width:720px;
                                        margin-left:auto;
                                        margin-right:auto;
                                    }
                                  </style>
                                </head>
                                <body>
                                  <div class="total-container">
                                    <div class="MuiPaper-root MuiPaper-elevation0" id="container-email-top" style="margin: 0px; background: transparent; padding: 0px; min-height: 450px;"><div style="padding: 20px 30px; background-color: rgb(255, 255, 255);" draggable="false"><img src="/static/media/addLogoSample.5fa53e23ea0a11a0631e.png" style="display: block; height: 90px; width: 200px; object-fit: contain; margin-left: auto; margin-right: auto;"></div><div background="#FFFFFF" draggable="false" style="background-color: rgb(255, 255, 255); padding: 0px 30px 20px;"><div style="font-size: 30px; color: rgb(0, 0, 0); width: 100%; font-weight: 700; letter-spacing: 0px; text-align: center; font-family: Poppins, Roboto;"><span style="white-space: pre-wrap;">Forgot Password</span></div></div><div background="#FFFFFF" padding="1" draggable="false" style="background-color: rgb(255, 255, 255); padding: 0px 30px 20px;"><div style="font-size: 18px; color: rgb(0, 0, 0); width: 100%; font-weight: 400; letter-spacing: 0px; text-align: left; font-family: Roboto, Roboto;"><span style="white-space: pre-wrap;"><p>{{%salutation_formal%}},</p><p>Hi, We hope you are doing well. Thank you for reaching us. If you lost your password Please start it from clicking the below link and reset code.</p><p>Please use this code from getting rid of anonymous actions : {{%code%}}</p><p>{{%signature_formal%}}</p></span></div></div><div draggable="false" style="padding: 20px 30px; background-color: rgb(214, 255, 230);"><a href="{{%system_forgot_password_link%}}" class="pointer-none" style="text-decoration: none;"><button style="display: block; outline: none; border: none; padding: 12px 24px; border-radius: 6px; margin-left: auto; margin-right: auto; background-color: rgb(0, 0, 0);"><span style="font-size: 16px; font-family: Poppins; color: rgb(255, 255, 255); width: 100%; font-weight: 500; letter-spacing: 0px; white-space: pre-wrap;">Reset Your Password</span></button></a></div><div background="#FFFFFF" padding="1" draggable="false" style="background-color: rgb(255, 255, 255); padding: 20px 30px;"><div style="font-size: 12px; color: rgb(0, 0, 0); width: 100%; font-weight: 400; letter-spacing: 0px; text-align: center; font-family: Poppins, Roboto;"><span style="white-space: pre-wrap;">If you donot wish to continue with your resetting your password then you can ignore this email</span></div></div><div draggable="false" style="background-color: rgb(255, 255, 255); padding: 20px 30px;"><div style="font-size: 12px; color: rgb(0, 0, 0); width: 100%; font-weight: 400; letter-spacing: 0px; text-align: center; font-family: Roboto, Roboto;"><span style="white-space: pre-wrap;">Ⓒ 2021 Copyright: Omnics.in</span></div></div></div>
                                  </div>
                                </body>
                              </html>',
            'previous_state' => 'eyJST09UIjp7InR5cGXECHJlc29sdmVkTmFtZSI6IlBhcGVyQ29udGFpbmVyIn0sImlzQ2FudmFzIjp0cnVlLCJwcm9wc8Q6aWQiOiJjyCstZW1haWwtdG9wIiwicGFkZGluZyI6MCwiYmFja2dyb3VuxC90cmFuc3BhcmVudMRcZGlzcGxhedZ8LCJjdXN0b20iOnt9LCJoaWRkZW4iOmZhbHNlLCJub2RlcyI6WyI1WjJxYWlSZ0U2IiwibGF5aTZWVlBkb8QNS0loekNhaVJVIiwic29UbHFFUER0MyIsIk92TjFONUhvS0wiLCJZUFEtWGxpRy0yIl0sImxpbmtlZE7GXnt9fc1H+gE3TWFpbuQAuucA2OoBNOcAr+kBNXRleHQiOiI8cD57eyVzYWx1dGF0aW9uX2Zvcm1hbCV9fSw8L3A+PHA+SGksIFdlIGhvcGUgeW91IGFyZSBkb2luZyB3ZWxsLiBUaGFua8UaZm9yIHJlYWNoxB11cy4gSWbFGGxvc3TECXIgcGFzc3dvcmQgUGxlYXNlIHN0YXJ0IGl0IGZyb20gY2xpY2vEPHRoZSBiZWxvdyDkAQYgYW5kIHJlc2V0IGNvZGUu5wCYx0N1c2UgdGhpc8UcxkhnZXR0xEdyaWQgb2YgYW5vbnltb3VzIGFj5ADjcyA6IHt7JcQwJX19x07kAQVpZ25hdHVyZeoBBMQdIuwCOkNvbG9yIjoiI2bFAeoCYkxlZuQBTzMwyhNSaWdo0BRUb3AiOiLLEUJvdOUCWiIyxBVmb250U2l65AHJMTjHEEZhbWlseSI6IlJvYm905AJQxBZXZcdeNDDEOWxldHRlclNwYWPlAu7FYsQn6QCwMMUBIizlAfhBbGlnbiI6ImzkALXvAxkjRsUB7AM8IjHyAyPsAmjuAyDnA1U65gPh+QMw9QLj6wMd+gLjQnV0dG9u/gLe8wGyZDZmZmXkA5zpBCpQ8gG70BztAcTQHeYBzeUBudAb7gHXYuUAslTlAM/HDckW6AGMY2VudOUEbMYXQm9yZGXkALjkALLGE/IBzOcBokhvcml65ACBbMRxNMoZVmVydGljxRcxMucB/OQBmeUDTFnkA5FQ5wOR8AJsUG9wcGluc+0CkzE27wJ7Nf8Ce/ADK25hdmln5QRmVXLkAJTkA3R5c3RlbeQDcWdvdF/oBB9f5AIaJX198gJ35wH8/wJy/wJy7AJy6wWC/wVV/wVV7AVV5wT4ZG9ub3Qgd2lzaCB0byDkAiNpbnVlIHdpdGjEIOQFLXPnBKvuBR50aGVuxUJjYW4gaWdub3LnBOTlBtv/BJH/BJHPFOsCxP8EkuYCQ/sCJv8Ek/8Ek/EDB/8Elf8Elf8CI/8CI+0CI+sHmP8CI/8CI+wCI+KSuCAyMDIxIENvcHly5AE5OiBPbW5pY3MuaeUEHf8B4v8B4v8B4vYB4jEy/wZy/wHf/wHf6wHf/wG6/wG6/wG66wky6gmG/wG6/wG67AG6RuUErOwFOv8BrP8BrP8IH/YBqzMw/wOLx103/wGs/wGs+wOL/wHD/wHD/wHD6gHD6wtW+gHDSGVhZFRhZ/8IE/sBpv8IE/8IE/8IE/UIE2ltYWdlVVJMIjoiyA5XaWR0aMUgySFI6AHIOckT/wNK6QEM/wGD/wGD6gGDfQ==',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        DB::table('emails_templates_samples')->insert([
            'name' => 'Newsletter Confirmation',
            'description' => '<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">
                                <head>
                                  <meta charset="utf-8" />
                                  <link rel="icon" href="/favicon.ico" />
                                  <meta name="viewport" content="width=device-width, initial-scale=1" />
                                  <!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch><o:AllowPNG/></o:OfficeDocumentSettings></xml><![endif]-->
                                  <meta name="theme-color" content="#000000" />
                                  <link
                                href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Sora:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Hahmlet:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=JetBrains Mono:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Andada Pro:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Epilogue:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Encode Sans:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Manrope:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Lora:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=BioRhyme:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Playfair Display:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Archivo:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Cormorant:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Spectral:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Raleway:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Work Sans:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Lato:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Anton:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Old Standard TT:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Nunito:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Source Sans Pro:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Oxygen:300,400,500,600,700"
                                rel="stylesheet"
                              /><link
                                href="https://fonts.googleapis.com/css?family=Open Sans:300,400,500,600,700"
                                rel="stylesheet"
                              />
                                  <title>React App</title>
                                  <style type="text/css">
                                    body {
                                      margin: 0;
                                      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto",
                                        "Oxygen", "Ubuntu", "Cantarell", "Fira Sans", "Droid Sans",
                                        "Helvetica Neue", sans-serif;
                                      -webkit-font-smoothing: antialiased;
                                      -moz-osx-font-smoothing: grayscale;

                                    }
                                    .total-container{
                                        max-width:720px;
                                        margin-left:auto;
                                        margin-right:auto;
                                    }
                                  </style>
                                </head>
                                <body>
                                  <div class="total-container">
                                    <div class="MuiPaper-root MuiPaper-elevation0" id="container-email-top" style="margin: 0px; background: transparent; padding: 0px; min-height: 450px;"><div draggable="false" style="padding: 20px 30px 10px; background-color: rgb(255, 255, 255);"><img src="/static/media/addLogoSample.5fa53e23ea0a11a0631e.png" style="display: block; height: 100px; width: 200px; object-fit: contain; margin-left: auto; margin-right: auto;"></div><div background="#FFFFFF" draggable="false" style="background-color: rgb(255, 255, 255); padding: 0px 30px 20px;"><div style="font-size: 30px; color: rgb(0, 0, 0); width: 100%; font-weight: 700; letter-spacing: 0px; text-align: center; font-family: Poppins, Roboto;"><span style="white-space: pre-wrap;"><p>Newsletter Confirmation</p></span></div></div><div background="#FFFFFF" padding="1" style="background-color: rgb(255, 255, 255); padding: 5px 30px;" draggable="false"><div style="font-size: 14px; color: rgb(0, 0, 0); width: 100%; font-weight: 400; letter-spacing: 0px; text-align: left; font-family: Poppins, Roboto;"><span style="white-space: pre-wrap;"><p><strong>Hello {{%first_name%}}</strong>,</p></span></div></div><div background="#FFFFFF" padding="1" draggable="false" style="background-color: rgb(255, 255, 255); padding: 0px 30px 20px;"><div style="font-size: 14px; color: rgb(0, 0, 0); width: 100%; font-weight: 400; letter-spacing: 0px; text-align: left; font-family: Poppins, Roboto;"><span style="white-space: pre-wrap;"><p>Please Approve shall we send email of the Newsletters</p><p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam</p></span></div></div><div draggable="false" style="padding: 5px 30px 20px; background-color: rgb(255, 255, 255);"><a href="{{%app_newsletters_activation_link%}}" class="pointer-none" style="text-decoration: none;"><button style="display: block; outline: none; border: none; padding: 12px 24px; border-radius: 6px; margin-left: auto; margin-right: auto; background-color: rgb(41, 199, 67);"><span style="font-size: 16px; font-family: Poppins; color: rgb(255, 255, 255); width: 100%; font-weight: 500; letter-spacing: 0px; white-space: pre-wrap;">Activate</span></button></a></div><div background="#FFFFFF" padding="1" style="background-color: rgb(255, 255, 255); padding: 5px 30px 20px;" draggable="false"><div style="font-size: 14px; color: rgb(0, 0, 0); width: 100%; font-weight: 400; letter-spacing: 0px; text-align: left; font-family: Poppins, Roboto;"><span style="white-space: pre-wrap;"><p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam</p></span></div></div><div draggable="false" style="padding: 5px 30px 20px; background-color: rgb(255, 255, 255);"><a href="{{%app_newsletters_activation_link%}}" class="pointer-none" style="text-decoration: none;"><button style="display: block; outline: none; border: none; padding: 12px 24px; border-radius: 6px; margin-left: auto; margin-right: auto; background-color: rgb(218, 37, 37);"><span style="font-size: 16px; font-family: Poppins; color: rgb(255, 255, 255); width: 100%; font-weight: 500; letter-spacing: 0px; white-space: pre-wrap;">Deactivate</span></button></a></div><div draggable="false" style="background-color: rgb(255, 255, 255); padding: 20px 30px;"><div style="font-size: 12px; color: rgb(0, 0, 0); width: 100%; font-weight: 400; letter-spacing: 0px; text-align: center; font-family: Poppins, Roboto;"><span style="white-space: pre-wrap;"><p>Ⓒ 2021 Copyright: Omnics.in</p></span></div></div></div>
                                  </div>
                                </body>
                              </html>',
            'previous_state' => 'eyJST09UIjp7InR5cGXECHJlc29sdmVkTmFtZSI6IlBhcGVyQ29udGFpbmVyIn0sImlzQ2FudmFzIjp0cnVlLCJwcm9wc8Q6aWQiOiJjyCstZW1haWwtdG9wIiwicGFkZGluZyI6MCwiYmFja2dyb3VuxC90cmFuc3BhcmVudMRcZGlzcGxhecd8UnNlIiwiY3VzdG9tIjp7fSwiaGlkZGVuIjpmYWxzZSwibm9kZXMiOlsiX2NES2lRSUtTdCIsIjlyVHRvWGVFecQ+Qkt4SjRfRzdBMSIsInRTekRkcjRUZW8iLCJvWDRiV1IwaDJPIiwiaFhFd3lNajRZLSIsIlZWX25RNWVGRkgiLCJnODlwajlnMFMyIl0sImxpbmtlZE7GeHt9fSzMe/oBRkhlYWRUYWfuAT/nAMXpAUDqARlDb2xvciI6IiNmxQHkAQXoAVdQ5gFKTGVmdCI6IjMw0xxSaWdo2R1Ub3AiOiIy1BtCb3TlAWkiMcQeaW1hZ2VVUkwiOiLIDldpZHRoxT7JIUhlx20xyhRBbGlnbuQCC2VudOYCM+4B101v8AHX5wIBOuYCjfkB5/UBgOsB7voBgE1haW7kAsXnAmf6AYR0ZXjkAIg8cD5OZXdzbGV0dGVyIENvbmZpcm1hdGlvbjwvcD4i7ALF8gGs5wLt7AGjxxPtAZrHFOYBkcsR6QGH5QGlZm9udFNpemUiOjMwxg5GYW1pbHnkA5tvcHBpbnPHJVfoAYo35QGK5gC7U3BhY+UDeMVhxCfpAK8wxQHkAxVleHTvAbPvA6UjRsUB8gHKRP8Byv8Byu8ByusDnv8Byv8Byu8BylBsZWFzZSBBcHByb3ZlIHNoYWxsIHdlIHNlbmQg5QSmIG9mIHRoZSDqAfRz5AHoPHA+TG9yZW0gaXBzdW0gZOQBOyBzaXQgYW1ldCwgY29uc2VjdGV0dWVyIGFkaXBpc+QBbSBlbGl0LCBzZWQgZGlhbSBub251bW15IG5pYmggZXVpc21vZCB0aW5jaWR1bnQgdXQgbGFvcmVldMZhZSBtYWduYSBhbGlxdWFt/wJw/wJw/wJw/QJwMTT/AnDHXTT/AnD8AnBs5AC09wJu6wY2IjH/Anz/Anz/Anx97QXm/wJ8/wJ87wJ84pK4IDIwMjEgQ29weXLkATI6IE9tbmljcy5p/wRM/wHc+gHc5QHI/wHdMTL/Ad3/Ad3/Ad39BgD/Abr/AbrwAbrrB636AbpCdXR0b27/B3//B3//B3//B3/tB3810xruAapi5QCxVOUAzscNyRbyBa3FF0JvcmRl5AC3NskT6QDJZGEyNTLEducCF0hvcml65ACBbMRxNMoZVmVydGljxRcxMucB0OQBs2VhY3RpdmF05AG6+wI45wJdNu8CRjX/AkbwAW9uYXZpZ+UHb1Vy5ACLe3slYXBwX27qBZ9f5wCQaW9uX+QCFCV9ffICaW3/Amn/AmnvAmnrCjD/Amn/Amn/Amn/Amn/Amn/Amn/Amn/Amn/Amk6IiMyOWM3NDP/Amn7AmlB/wJn/wJn/wJn/wJn/wJn/wJn/wJn/wJn6QJn6wyK/waK/waK7waK/wjK/wjK/wjK/wjK/wjK/wbu/gbu6wTW/wjK/wbt/wbt/wbt/wjK/AjK7AHN/wJI/wJI7AJI6w75/wJI/wJI7wJIPHN0cm9uZz5IZWxsbyB7eyVmaXJzdF9uYW1lJX19PC/HHyz/Ae//Ae//Ae/uAe/EFP8B7v8B7v8B7v8B7v8B7v8B7v8B7v8B7sQ7fX0=',
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
