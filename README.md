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
 - [Magento] Community 2.0 | 2.1
 - [PHP] 5.5+
 - [SPL]
 - [cURL]
 - [DOM]


Instalação
-----------
Navegue até o diretório raíz da sua instalação do Magento 2 e siga os seguintes passos:

1. Instale via packagist 
   - ```composer require pagseguro/magento2```
       - Neste momento, podem ser solicitadas suas credenciais de autenticação do Magento. Caso tenha alguma dúvida, há uma descrição de como proceder nesse [link da documentação oficial](http://devdocs.magento.com/guides/v2.0/install-gde/prereq/connect-auth.html).
2. Execute os comandos:
   - ```php bin/magento setup:upgrade```
   - ```php bin/magento setup:static-content:deploy``` ou ```php bin/magento setup:static-content:deploy pt_BR```, de acordo com as configurações da sua loja.
3. Dê permissões as pastas var/ pub/
   - ```chmod -R 777 var/ pub/```


Atualização
-----------
É altamente recomendado que você tenha um ambiente de testes para validar alterações e atualizações antes de atualizar sua loja em produção. É recomendado também que seja feito um **backup** da sua loja e informações importantes antes de executar qualquer procedimento de atualização/instalação.

A atualização do módulo do PagSeguro é feita através do **composer** e pode ser feita de diversas maneiras, de acordo com suas preferências. Uma forma é através dos comandos:
1. ```composer update pagseguro/magento2```
2. ```composer update pagseguro/pagseguro-php-sdk```
3. ```php bin/magento setup:upgrade```
4. ```php bin/magento setup:static-content:deploy``` ou ```php bin/magento setup:static-content:deploy pt_BR```, de acordo com as configurações da sua loja.

**Observações** 
- Em alguns casos, o Magento não atualiza os arquivos estáticos gerados, podendo ser necessário atualizar os mesmos via interface administrativa, comandos do terminal ou removendo diretamente conteúdo da pasta *pub/static/frontend/Magento/seu_tema/seu_idioma/UOL_PagSeguro*.
- Em seguida, executar novamente o comando ```php bin/magento setup:static-content:deploy``` ou ```bin/magento setup:static-content:deploy pt_BR```, de acordo com as configurações da sua loja.


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


Dúvidas?
----------
---
Caso tenha dúvidas ou precise de suporte, acesse nosso [fórum].


Changelog
---------
1.3.0
- Adicionada validação e mensagens de erro (frontend) nos formulários do checkout transparente

1.2.6
- Melhoria na configuração do log na interface administrativa
- Adicionada seção de atualização do módulo e atualização geral da documentação (README.md)
- Correção de bugs quando o pedido deixava de existir ou a sessão era encerrada
- Correçao para aceitar CVV de 4 digitos
- Melhoria no acesso aos dados do endereço do cliente

1.2.1
- Alterada a biblioteca JavaScript utilizada nas máscaras.

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


  [API de Pagamentos]: https://dev.pagseguro.uol.com.br/documentacao/pagamentos
  [API de Notificações]: https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-notificacoes.html
  [fórum]: https://comunidade.pagseguro.uol.com.br/hc/pt-br/community/topics
  [Pagamentos via API]: https://pagseguro.uol.com.br/integracao/pagamentos-via-api.jhtml
  [Notificação de Transações]: https://pagseguro.uol.com.br/integracao/notificacao-de-transacoes.jhtml
  [Magento]: https://www.magentocommerce.com/
  [PHP]: http://www.php.net/
  [SPL]: http://php.net/manual/en/book.spl.php
  [cURL]: http://php.net/manual/en/book.curl.php
  [DOM]: http://php.net/manual/en/book.dom.php
  [GitHub]: https://github.com/pagseguro/magento2
