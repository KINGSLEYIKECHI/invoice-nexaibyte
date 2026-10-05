import { describe,it,expect } from 'vitest'
import { toMinor,formatMoney,currencyDigits,currencyList } from './money'
describe('currency money handling',()=>{
 it('supports country search metadata and zero three and four decimal currencies',()=>{expect(currencyList.length).toBeGreaterThan(150);expect(currencyDigits('JPY')).toBe(0);expect(currencyDigits('KWD')).toBe(3);expect(currencyDigits('CLF')).toBe(4);expect(currencyList.find(c=>c.code==='USD')?.countries.length).toBeGreaterThan(0)})
 it('converts entered prices exactly and rejects unsupported precision',()=>{expect(toMinor('1.23','USD')).toBe(123);expect(toMinor('1.234','KWD')).toBe(1234);expect(toMinor('100','JPY')).toBe(100);expect(()=>toMinor('1.2','JPY')).toThrow();expect(()=>toMinor('1.234','USD')).toThrow();expect(()=>toMinor('-1','USD')).toThrow()})
 it('formats the saved snapshot precision rather than assuming cents',()=>{expect(formatMoney(1234,'KWD',3)).toContain('1.234');expect(formatMoney(100,'JPY',0)).toContain('100');expect(formatMoney(123,'USD',2)).toContain('1.23')})
})
