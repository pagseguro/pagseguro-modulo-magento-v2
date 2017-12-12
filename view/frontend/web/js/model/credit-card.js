/**
 * 2007-2017 [PagSeguro Internet Ltda.]
 *
 * NOTICE OF LICENSE
 *
 *Licensed under the Apache License, Version 2.0 (the "License");
 *you may not use this file except in compliance with the License.
 *You may obtain a copy of the License at
 *
 *http://www.apache.org/licenses/LICENSE-2.0
 *
 *Unless required by applicable law or agreed to in writing, software
 *distributed under the License is distributed on an "AS IS" BASIS,
 *WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *See the License for the specific language governing permissions and
 *limitations under the License.
 *
 *  @author    PagSeguro Internet Ltda.
 *  @copyright 2016 PagSeguro Internet Ltda.
 *  @license   http://www.apache.org/licenses/LICENSE-2.0
 */
//function setCreditCardSessionId(session) {
//  return PagSeguroDirectPayment.setSessionId(session)
//}
//
//function getSenderHash() {
//  return PagSeguroDirectPayment.getSenderHash()
//}
//
//function assignCreditCardHash() {
//  setTimeout(function () {
//    document.getElementById('creditCardHash').value = getSenderHash()
//  }, 500)
//}

function validateCreditCard(self) {
  if (self.validity.valid && removeNumbers(unmask(self.value)) === "" && (self.value.length >= 14 && self.value.length <= 22)) {
    displayError(self, false)
    return true
  } else {
    displayError(self)
    return false
  }
}

function validateCardHolder (self) {
    if (self.validity.tooShort || !self.validity.valid || removeLetters(unmask(self.value)) !== "") {
      displayError(self)
      return false
    } else {
      displayError(self, false)
      return true
    }
  }
  
  function validateCreditCardHolderBirthdate (self) {
    var val = self.value
    var date_regex = /^(0[1-9]|1\d|2\d|3[01])\/(0[1-9]|1[0-2])\/(19|20)\d{2}$/
    if (!(date_regex.test(val))) {
      displayError(self)
      return false
    } else {
      displayError(self, false)
      return true
    }
  }
  
  function validateCreditCardMonth (self) {
    if (self.validity.valid && self.value !== "") {
      displayError(self, false)
      return true
    } else {
      displayError(self)
      return false
    }
  }
  
  function validateCreditCardYear (self) {
    if (self.validity.valid && self.value !== "") {
      displayError(self, false)
      return true
    } else {
      displayError(self)
      return false
    }
  }

function cardInstallmentOnChange(data) {
  data = JSON.parse(data.value)
  document.getElementById('creditCardInstallment').value = data.quantity
  document.getElementById('creditCardInstallmentValue').value = data.installmentAmount
  document.getElementById('card_total').innerHTML = 'R$ ' + data.totalAmount
}

function cardInstallment(data) {
  var select = document.getElementById('card_installment_option')
  data = data[Object.getOwnPropertyNames(data)[0]]
  data.forEach(function (item) {
    select.options[select.options.length] = new Option(item.quantity + 'x de R$ ' + item.installmentAmount,
            JSON.stringify(item))
  })
  if (data) {
    select.removeAttribute('disabled')
  }
}

function validateCreditCardInstallment (self) {
    if (self.validity.valid && self.value != "null") {
      displayError(self, false)
      return true
    } else {
      displayError(self)
      return false
    }
  }

function getInstallments(brand) {
  PagSeguroDirectPayment.getInstallments({
    amount: document.getElementById('grand_total').value,
    brand: brand,
    success: function (response) {
      cardInstallment(response.installments)
    },
    error: function (response){
      console.log('erro ao gerar parcelamento');
    },
  })
}

function getBrand(self) {
  var select = document.getElementById('card_installment_option');
  select.options.length = 0;
  select.options[0] = new Option('Escolha o N° de parcelas', null, true, true);
  select.options[0].disabled = true
  document.getElementById('card_total').innerHTML = 'selecione o número de parcelas';
  PagSeguroDirectPayment.setSessionId(document.getElementById('pagseguroCcSessionId').value);
  if (validateCreditCard(self)) {
    PagSeguroDirectPayment.getBrand({
      cardBin: unmask(document.getElementById('pagseguro_credit_card_number').value),
      success: function (response) {
        document.getElementById('creditCardBrand').value = response.brand.name
        getInstallments(response.brand.name)
        displayError(document.getElementById('pagseguro_credit_card_number'), false)
      },
      error: function () {
        console.log('erro na chamada');
      },
      complete: function(response) {
          //console.log('tratamento comum para todas chamadas');////
      }
    });
  } else {
    displayError(document.getElementById('pagseguro_credit_card_number'))
  }
  return false;
}


function validateCreditCardCode(self) {
  if (self.validity.tooLong || self.validity.tooShort || !self.validity.valid) {
    displayError(self)
    return false
  } else {
    displayError(self, false)
    return true
  }
}

function validateCreditCardForm() {
  if (
   validateCreditCard(document.querySelector('#pagseguro_credit_card_number')) &&
   validateDocumentFinal(document.querySelector('#creditCardDocument')) &&
   validateCardHolder(document.querySelector('#creditCardHolder')) &&
   validateCreditCardHolderBirthdate(document.querySelector('#creditCardHolderBirthdate')) &&
   validateCreditCardMonth(document.querySelector('#creditCardExpirationMonth')) &&
   validateCreditCardYear(document.querySelector('#creditCardExpirationYear')) &&
   validateCreditCardCode(document.querySelector('#creditCardCode')) &&
   validateCreditCardInstallment(document.querySelector('#card_installment_option'))
  ) {
   return true;
  }
  
  validateCreditCard(document.querySelector('#pagseguro_credit_card_number'))
  validateDocumentFinal(document.querySelector('#creditCardDocument'))
  validateCardHolder(document.querySelector('#creditCardHolder'))
  validateCreditCardHolderBirthdate(document.querySelector('#creditCardHolderBirthdate'))
  validateCreditCardMonth(document.querySelector('#creditCardExpirationMonth'))
  validateCreditCardYear(document.querySelector('#creditCardExpirationYear'))
  validateCreditCardCode(document.querySelector('#creditCardCode'), false)
  validateCreditCardInstallment(document.querySelector('#card_installment_option'))
  return false;
}

function validateCreateToken() {
  if(validateCreditCard(document.querySelector('#pagseguro_credit_card_number')) 
    && validateCreditCardMonth(document.querySelector('#creditCardExpirationMonth'))
    && validateCreditCardYear(document.querySelector('#creditCardExpirationYear'))
    && validateCreditCardCode(document.querySelector('#creditCardCode'))
    && document.getElementById('creditCardBrand').value !== ""
    ) {
      return true
  }

  validateCreditCard(document.querySelector('#pagseguro_credit_card_number'));
  validateCreditCardMonth(document.querySelector('#creditCardExpirationMonth'));
  validateCreditCardYear(document.querySelector('#creditCardExpirationYear'));
  validateCreditCardCode(document.querySelector('#creditCardCode'));

  return false;
}

/**
 * Return the value of 'el' without letters
 * @param {string} el
 * @returns {string}
 */
function removeLetters(el) {
  return el.replace(/[a-zA-Z]/g, '');

}

/**
 * Return the value of 'el' without numbers
 * @param {string} el
 * @returns {string}
 */
function removeNumbers(el) {
  return el.replace(/[0-9]/g, '');
}