
# See also errors in function_invocation.coffee, ranges.coffee

i = 10
results = while i -= 1 when i % 2 is 0
  i * 2

###
i = 10
results = while i > 0
  i--
  switch i % 2
    when 1 then i
    when 0 then break

(success and= typeof n is 'number') for n in flatten [0, [[[1]], 2], 3, [4]]

eq 4, 0b100

result = ident
  one: 1
  two: 2 for i in [1..3]

eq 1, [5 in []].length

arr = [1..4]

arrayEq [1, 2, 3, 4], shared[a..b]
###

