import { describe,it,expect } from 'vitest'
import { formatNaira,toKobo } from '../utils/formatNaira'
import { formatDate,dateInput } from '../utils/formatDate'
describe('money and dates',()=>{
 it('formats integer kobo in naira',()=>expect(formatNaira(107500)).toContain('1,075.00'))
 it('rounds naira input to integer kobo',()=>{expect(toKobo(150.25)).toBe(15025);expect(toKobo(0.29)).toBe(29)})
 it('keeps calendar dates stable',()=>expect(formatDate('2026-10-02')).toBe('2 Oct 2026'))
 it('builds valid date inputs',()=>expect(dateInput()).toMatch(/^\d{4}-\d{2}-\d{2}$/))
})