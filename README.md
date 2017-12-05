# Pagseguro-VirtuemartBrasil3
Alternativa para esta extensão: https://github.com/luizwbr/virtuemart
------------------------
Método de pagamento não-oficial, que integra o VirtueMart com o PagSeguro, usando formulário html.
* Virtuemart 3.x / Joomla 3.x

Tutorial
-------

* Para instalar no Virtuemart, basta compactar os arquivos e usar o instalador do Joomla 3.x

- Passo 1 - Habilite o plugin aqui Administrar Plugins

- Passo 2 - Instale Plugin por esta tela Métodos de pagamento

- Passo 2.1 - Clique em Novo Método de Pagamento e preencha as informações:

* Nome do Pagamento: Pagseguro
* Publicado: Sim
* Descrição do pagamento: Pague com Pagseguro
* Método de pagamento: Pagseguro
* Grupo de Compradores: -default-

- Passo 2.2 - Clique em Salvar.

- Passo 3 - Na aba configurações, preencha os dados:

* Logotipos:
* Email pagseguro: Email da conta no Pagseguro
* Token: Dentro do painel do Pagseguro tem a opção de gerar o token (https://pagseguro.uol.com.br/preferencias/integracoes.jhtml) 
* Tipo de Frete Padrão ( caso seja passado frete, esse parâmetro é desconsiderado)

- Passo 4 - Configurar o retorno

Neste link: https://pagseguro.uol.com.br/preferencias/integracoes.jhtml, deverá ser inserido no campo de Notificação da transação, a seguinte url:

site.com.br//index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification

-- Status de pedidos
* Completo: status transação Enviada
* Aprovado: Status do Pedido quando Aprovada a transação
* Em Análise: Status do pedido em análise
* Cancelado: Status do Pedido quando Cancelada a transação
* Aguardando Pagto: Status Pendente do Pedido
* Paga: Status Pago do Valor da Transação
* Disponível: Status Disponível do Valor da Transação
* Devolvida: Status Devolvido do Valor da Transação
* Em disputa: Status em disputa do Valor da Transação

Licença
-------

Copyright 2017 Luiz Felipe Weber.

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.


Dúvidas?
----------

Em caso de dúvidas acesse o site http://weber.eti.br.

Contato
----------

Luiz Felipe Weber
weber@weber.eti.br

Novidades
-------------

3.0.0 - Corrigido o problema dos cupons de desconto ( Obrigado Henrique Galdolfi )

Contribuições
-------------

Achou e corrigiu um bug ou tem alguma feature em mente e deseja contribuir?

* Faça um fork
* Adicione sua feature ou correção de bug (git checkout -b my-new-feature)
* Commit suas mudanças (git commit -am 'Added some feature')
* Rode um push para o branch (git push origin my-new-feature)
* Envie um Pull Request
* Obs.: Adicione exemplos para sua nova feature. Se seu Pull Request for relacionado a uma versão específica, o Pull Request não deve ser enviado para o branch master e sim para o branch correspondente a versão.
