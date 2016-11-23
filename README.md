Módulo de integração PagSeguro para Magento 2.x
====================================================

[![Code Climate](https://codeclimate.com/github/pagseguro/magento2/badges/gpa.svg)](https://codeclimate.com/github/pagseguro/magento2)

---
Descrição
---------
---
Com o módulo instalado e configurado, você pode pode oferecer o PagSeguro como opção de pagamento em sua loja. O módulo utiliza as seguintes funcionalidades que o PagSeguro oferece na forma de APIs:

 - Integração com a [API de Pagamentos]
 - Integração com a [API de Notificações]


Requisitos
----------
---
 - [Magento] Community 2.0.x
 - [PHP] 5.5.0+
 - [SPL]
 - [cURL]
 - [DOM]


Instalação
-----------

1. Instale via packagist 
	 - ```composer require pagseguro/magento2```
2. Execute os comandos: 
	 - ```php bin/magento setup:upgrade```
	 - ```php bin/magento setup:static-content:deploy```
3. Dê permissões as pastas var/ pub/
	 - ```chmod 777 -R var/ pub/```
	
Inputs
---------
---
| Dados do comprador         |Tipo  | Esperado                                                                       |
| ---------------------------|:----:|:------------------------------------------------------------------------------:| 
| Email                      | {Pattern - ^([a-zA-Z0-9_])+([@])+([a-zA-Z0-9_])+([.])+([a-zA-Z0-9_])}| email@email.em |
| Name / Nome                | {String}                                                             | Nome           | 
| Last Name  / Sobrenome     | {String}                                                             | Sobrenome      |  
| Company  / Empresa         | {String}                                                             | Empresa        | 
| Address / Endereço         | {String, Integer}                                                    |Endereço, Numero| 
| Address 2 / Bairro /Endereço (Linha 2) | {String}                                                          | Bairro        | 
| PostCode / CEP              | {Integer or String}                                            | 99999999 / 99999-999 |
| City / Cidade              | {String}                                                             |    Cidade      |
| Country / País             | {String}                                                             | País           |
| State or Province / Estado | {String}                                                             | Estado         |
| Aditional information / Informações adicionais | {String}                                         |Complemento     |
| Phone / Telefone residencial | {Integer} - {DDD+NUMBER}                                             | 99999999999  |
| Cell Phone / Telefone celular | {Integer} - {DDD+NUMBER}                                             | 99999999999  |

Changelog
---------
1.2.0
- Adicionada opção para utilizar o Checkout Transparente.

1.1.0
- Possibilidade de consultar e solicitar o cancelamento de transações;
- Possibilidade de consultar e solicitar o estorno de transações;
- Possibilidade de definir descontos com base no meio de pagamento escolhido durante o checkout PagSeguro;

1.0.0
- Adicionando opção para utilização do Checkout Lightbox. 
- Integração com API de Notificação.
- Integração com API de Pagamento do PagSeguro.
- Configuração do Setup do módulo.
- Adicionado meio de pagamento ao Magento2
- Versão inicial.

Licença
-------
---
Copyright 2016 PagSeguro Internet LTDA.

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.


Notas
---------
---
 - O PagSeguro somente aceita pagamento utilizando a moeda Real brasileiro (BRL).
 - Certifique-se que o email e o token informados estejam relacionados a uma conta que possua o perfil de vendedor ou empresarial.
 - Certifique-se que tenha definido corretamente o charset de acordo com a codificação (ISO-8859-1 ou UTF-8) do seu sistema. Isso irá prevenir que as transações gerem possíveis erros ou quebras ou ainda que caracteres especiais possam ser apresentados de maneira diferente do habitual.
 - Para que ocorra normalmente a geração de logs, certifique-se que o diretório e o arquivo de log tenham permissões de leitura e escrita.

Contribuições
-------------
---
Achou e corrigiu um bug ou tem alguma feature em mente e deseja contribuir?

* Faça um fork.
* Adicione sua feature ou correção de bug.
* Envie um pull request no [GitHub].
* Obs.: O Pull Request não deve ser enviado para o branch master e sim para o branch correspondente a versão ou para a branch de desenvolvimento.


  [API de Pagamentos]: https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-pagamentos.html
  [API de Notificações]: https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-notificacoes.html
  [fórum]: http://forum.pagseguro.uol.com.br/
  [Pagamentos via API]: https://pagseguro.uol.com.br/integracao/pagamentos-via-api.jhtml
  [Notificação de Transações]: https://pagseguro.uol.com.br/integracao/notificacao-de-transacoes.jhtml
  [Magento]: https://www.magentocommerce.com/
  [PHP]: http://www.php.net/
  [SPL]: http://php.net/manual/en/book.spl.php
  [cURL]: http://php.net/manual/en/book.curl.php
  [DOM]: http://php.net/manual/en/book.dom.php
  [GitHub]: https://github.com/pagseguro/magento2

