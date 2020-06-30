1.12.0
- Checkout sem endereço (para produtos do tipo 'virtual' e 'downloadable')
- Habilitar/desabilitar recuperação de carrinho do PagSeguro via admin
- Tela de listar transações no admin, permitindo ver detalhes da transação
- Estorno parcial
- Disconto por meio de pagamento via configuração do módulo (admin) para o checkout padrão/lightbox
- Atualizada versão do pagseguro-php-sdk no composer.json para utilizar as versões 4.+
- Adicionada compatibilidade com endereços de 4 linhas, no formato: 1 rua/endereço, 2 número, 3 complemento, 4 bairro (padrão brasileiro)
- Valida se o telefone do comprador foi configurado antes de tentar usar o telefone do endereço de entrega
- Fix: Corrigido id dos itens do pedido (carrinho) enviados para o PagSeguro

1.4.0
- Alterado o fluxo do checkout transparente (na própria tela de checkout do Magento)
- Alterada a forma de configurar o módulo e os meios de pagamento do PagSeguro, que agora são configurados individualmente.
- Melhorias gerais e correções de bugs: transações do admin, css muito abrangente, remoção de arquivos velhos e desnecessários, refatorações.

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