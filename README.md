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
 - [Magento] Community 2.0.8 | 2.1.0 até a versão 2.1.9
 - [PHP] 5.5.0+
 - [SPL]
 - [cURL]
 - [DOM]


Instalação
-----------
> É altamente recomendado que você tenha um ambiente de testes para validar alterações e atualizações antes de atualizar sua loja em produção. É recomendado também que seja feito um **backup** da sua loja e informações importantes antes de executar qualquer procedimento de atualização/instalação.

Navegue até o diretório raíz da sua instalação do Magento 2 e siga os seguintes passos:

> A instalação do módulo é feita utilizando o Composer. Para baixar e instalar o Composer no seu ambiente acesse https://getcomposer.org/download/ e caso tenha dúvidas de como utilizá-lo consulte a [documentação oficial do Composer](https://getcomposer.org/doc/).

1. Instale via packagist 
   - ```composer require pagseguro/magento2```
       - Neste momento, podem ser solicitadas suas credenciais de autenticação do Magento. Caso tenha alguma dúvida, há uma descrição de como proceder nesse [link da documentação oficial](http://devdocs.magento.com/guides/v2.0/install-gde/prereq/connect-auth.html).
2. Execute os comandos:
   - ```php bin/magento setup:upgrade```
   - ```php bin/magento setup:static-content:deploy``` ou ```php bin/magento setup:static-content:deploy pt_BR```, de acordo com as configurações da sua loja.
3. Cheque e, caso necessário, configure as permissões corretas para seus diretórios. Por exemplo, para configrar a permissão 777 para as pastas var/ pub/ execute:
   - ```chmod 777 -R var/ pub/```
4. Pode ser necessário atualizar o cache da sua loja ao finalizar o processo.
5. Acesse a seção do PagSeguro através da interface administrativa da sua loja e configure suas credenciais e meios de pagamento.


Atualização
-----------
> É altamente recomendado que você tenha um ambiente de testes para validar alterações e atualizações antes de atualizar sua loja em produção. É recomendado também que seja feito um **backup** da sua loja e informações importantes antes de executar qualquer procedimento de atualização/instalação.

A atualização do módulo do PagSeguro é feita através do **composer** e pode ser feita de diversas maneiras, de acordo com suas preferências. Uma forma é através dos comandos:
1. ```composer update pagseguro/magento2```
2. ```composer update pagseguro/pagseguro-php-sdk```
3. ```php bin/magento setup:upgrade```
4. ```php bin/magento setup:static-content:deploy``` ou ```php bin/magento setup:static-content:deploy pt_BR```, de acordo com as configurações da sua loja.
5. Cheque e, caso necessário, configure as permissões corretas para seus diretórios.
6. Pode ser necessário atualizar o cache da sua loja ao finalizar o processo.
5. Acesse a seção do PagSeguro através da interface administrativa da sua loja, confira as informações e configurações do PagSeguro e seus meios de pagamento e clique no botão para salvar.

**Observações** 
- Em alguns casos, o Magento não atualiza os arquivos estáticos gerados, podendo ser necessário atualizar os mesmos via interface administrativa, comandos do terminal ou removendo diretamente conteúdo da pasta *pub/static/frontend/Magento/seu_tema/seu_idioma/UOL_PagSeguro*.
- Em seguida, executar novamente o comando ```php bin/magento setup:static-content:deploy``` ou ```bin/magento setup:static-content:deploy pt_BR```, de acordo com as configurações da sua loja.

Configuração
------------
---
Para acessar e configurar o módulo acesse o menu PagSeguro -> Configurações. As opções disponíveis estão descritas abaixo.

 -------------------------
 **Configurações Gerais**
 
 - **ambiente**: especifica em que ambiente as transações serão feitas *(produção/sandbox)*.
 - **e-mail**: e-mail cadastrado no PagSeguro.
 - **token**: token cadastrado no PagSeguro.
 - **url de redirecionamento**: ao final do fluxo de pagamento no PagSeguro, seu cliente será redirecionado automaticamente para a página de confirmação em sua loja ou então para a URL que você informar neste campo. Para ativar o redirecionamento ao final do pagamento é preciso ativar o serviço de [Pagamentos via API]. Obs.: Esta URL é informada automaticamente e você só deve alterá-la caso deseje que seus clientes sejam redirecionados para outro local.
 - **url de notificação**: sempre que uma transação mudar de status, o PagSeguro envia uma notificação para sua loja. **O valor padrão que deve ser utilizado pelo módulo é: http://www.minhaloja.com.br/index.php/pagseguro/notification/response**
     - *Observação: Esta URL só deve ser alterada caso você deseje receber as notificações em outro local.*
 - **charset**: codificação do seu sistema (ISO-8859-1 ou UTF-8).
 - **ativar log**: ativa/desativa a geração de logs.
 - **diretório**: informe o local e nome do arquivo a partir da raíz de instalação do Magento onde se deseja criar o arquivo de log. Ex.: var/log/pagseguro.log. 
     - *Por padrão o módulo virá configurado para salvar o arquivo de log em var/log/pagseguro.log*.
 - **listar transações abandonadas?**: ativa/desativa a pesquisa de transações que foram abandonadas no checkout do PagSeguro.
 - **transações -> abandonadas**: permite consultar as transações que foram abandonadas nos últimos 10 dias, desta forma você pode enviar emails de recuperação de venda. O e-mail conterá um link que redirecionará o comprador para o fluxo de pagamento, exatamente no ponto onde ele parou.
 - **habilitar recuperação de carrinho**: Habilita a recuperação de carrinho do PagSeguro. (por padrão está desabilitada)
 - **listar parcelamento**: Habilita a exibição de uma listagem de parcelas na tela de visualização do produto. (Irá exibir o maior parcelamento disponível para o produto na tela de exibição do mesmo)
 
 -------------------------
 **Configurar Tipos de Checkout**
 Nesta seção você irá configurar os meios de pagamento do PagSeguro que deseja disponibilizar na sua loja.
 > Consulte na sua conta do PagSeguro os meios de pagamento que estão habilitados.
 
 - *PagSeguro (Padrão ou Lightbox)*
   - **ativar**: ativa/desativa o meio de pagamento PagSeguro (padrão ou lightbox).
   - **checkout**: especifica o modelo de checkout que será utilizado. É possível escolher entre checkout padrão ou checkout lightbox.
   - **nome de exibição**: define o nome que será utilizado para o meio de pagamento na tela de checkout.
   - **posição na tela de checkout (Sort Order)**: Configura a ordem de exibição deste meio de pagamento na sua loja. Esta ordem é  relativa à todos os outros meios de pagamento configurados na sua loja.
   - **oferecer desconto para ...**: ativa/desativa desconto para checkouts por meio de pagamento (cartão de crédito, boleto, débito online, depósito em conta e saldo pagseguro)
   - **percentual de desconto**: define o percentual de desconto a ser concedido para o meio de pagamento escolhido (Aceita valores de 0.01 à 99.99)
 
 - *Checkout Transparente - Cartão de Crédito*
   - **ativar**: ativa/desativa o meio de pagamento Checkout Transparente - Cartão de Crédito.
   - **nome de exibição**: define o nome que será utilizado para esse meio de pagamento na tela de checkout.
   - **posição na tela de checkout (Sort Order)**: Configura a ordem de exibição deste meio de pagamento na sua loja. Esta ordem é  relativa à todos os outros meios de pagamento configurados na sua loja.
 
 
 - *Checkout Transparente - Boleto Bancário*
   - **ativar**: ativa/desativa o meio de pagamento Checkout Transparente - Boleto Bancário.
   - **nome de exibição**: define o nome que será utilizado para esse meio de pagamento na tela de checkout.
   - **posição na tela de checkout (Sort Order)**: Configura a ordem de exibição deste meio de pagamento na sua loja. Esta ordem é  relativa à todos os outros meios de pagamento configurados na sua loja.
 
 
 - *Checkout Transparente - Débito Online*
   - **ativar**: ativa/desativa o meio de pagamento Checkout Transparente - Débito Online.
   - **nome de exibição**: define o nome que será utilizado para esse meio de pagamento na tela de checkout.
   - **posição na tela de checkout (Sort Order)**: Configura a ordem de exibição deste meio de pagamento na sua loja. Esta ordem é  relativa à todos os outros meios de pagamento configurados na sua loja.
 
 
 Transações
------------
---
 Para realizar consultas e outras operações acesse o menu PagSeguro -> *Transação*, onde *Transação* pode ser escolhida as opções: Conciliação, Abandonadas, Cancelamento, Estorno. As opções disponíveis estão descritas abaixo:
 
 - **abandonadas**: permite pesquisar as transações que foram abandonadas dentro da quantidade de dias definidos para a pesquisa.
 - **cancelamento**: esta pesquisa retornará todas as transações que estejam com status "em análise" e "aguardando pagamento", dentro da quantidade de dias definidos para a pesquisa. Desta forma você pode solicitar o cancelamento destas transações.
 - **conciliação**: permite consultar as transações efetivadas no PagSeguro nos últimos 30 dias. A pesquisa retornará um comparativo com o status das transações em sua base local e o status atual da transação no PagSeguro, desta forma você pode identificar e atualizar transações com status divergentes.
 - **estorno**: esta pesquisa retornará todas as transações que estejam com status "paga", "disponível" e "em disputa", dentro da quantidade de dias definidos para a pesquisa. Desta forma você pode solicitar o estorno dos valores pagos para seus compradores.

 >  É aconselhável que antes de usar as funcionalidades de **estorno** ou **cancelamento** você faça a **conciliação** de suas transações para obter os status mais atuais.

Inputs
---------
---
| Dados do comprador         |Tipo  | Esperado                                                                       |
| ---------------------------|:----:|:------------------------------------------------------------------------------:| 
| Email                      | {Pattern - ^([a-zA-Z0-9_])+([@])+([a-zA-Z0-9_])+([.])+([a-zA-Z0-9_])}| email@email.em |
| Name / Nome                | {String}                                                             | Nome           | 
| Last Name  / Sobrenome     | {String}                                                             | Sobrenome      |  
| Company  / Empresa         | {String}                                                             | Empresa        | 
| Configuração de endereço de 4 linhas:
| Address 1 / Endereço 1 / Rua         | {String}                                                    |Endereço (rua)|
| Address 2 / Endereço 2 / Número         | {Integer}                                                |Número        |
| Address 3 / Endereço 3 / Complemento         | {String}                                            |Complemento   |
| Address 4 / Endereço 4 / Bairro         | {String}                                                 |Bairro        |
| Configuração de endereço padrão Magento 2 (2 linhas):
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
Para consultar o log de alterações acesse o arquivo [CHANGELOG.md](CHANGELOG.md).

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
