import currencies from '../data/currencies.json'
export const currencyList=currencies
export function currencyDigits(code='NGN'):number{return currencies.find(c=>c.code===code)?.minor_units??2}
export function moneyFactor(code='NGN',digits=currencyDigits(code)):number{return 10**digits}
export function formatMoney(minor=0,currency='NGN',digits=currencyDigits(currency)):string{return new Intl.NumberFormat('en-NG',{style:'currency',currency,currencyDisplay:'code',minimumFractionDigits:digits,maximumFractionDigits:digits}).format(minor/(10**digits))}
export function toMinor(value:number|string,currency='NGN'):number{
 const digits=currencyDigits(currency),text=String(value);if(!/^\d+(?:\.\d+)?$/.test(text))throw new Error('Enter a non-negative amount.')
 const [whole,fraction='']=text.split('.');if(fraction.length>digits)throw new Error('Use at most '+digits+' decimal places for '+currency+'.')
 const amount=Number(whole)*10**digits+Number(fraction.padEnd(digits,'0'));if(!Number.isSafeInteger(amount))throw new Error('Amount exceeds the supported limit.');return amount
}
