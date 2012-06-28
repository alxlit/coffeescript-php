
# See also errors in function_invocation.coffee, ranges.coffee

foo = 'bar' if something.kinda_stupidly.long \
  and blah.blah.baz is 'something'

a = {}
b = {}
c = {}

###
[a, [b]] = [c]

a={}; b={}; c={}; d={}; e={}; f={}; g={}; h={}; i={}
[u, [v, w..., x], y..., z] = [a, [b, c, d, e], f, g, h, i]

class Parent
  method: (args...) -> @args = args

oops = (x, args...) ->

for a in [1..9] then \
for b in [1..9]
  c = Math.sqrt a*a + b*b

i = 10
results = while i -= 1 when i % 2 is 0
  i * 2

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

