Type Auto Mapper
===


Useage
----

Typical Use Case:

- Create a REST API client using CURL
- Serailize the API responses into stdClass objects 
- Create simple classes to match the object formats in the response
  - Add types
  - Annotate arrays - including arrays of objects or arrays of scalars
  - Supports nesting
- Parse the type mapper the target class name and the stdClass input object -> returns an object fo type target class with all values parsed 


If a value cannot be parsed or is not part of the target class properties then it will be skipped.



Notes
---

- Create a re-usable mapping process between API responses and simple classes
- Once the API response is transformed we can use concrete classes in the rest of the code. good for:
  - Testing
  - Auto complete
  - Avoiding errors
  - clear and legible code within the business logic, no bleed in from the API client.



Improvements
---

- Enums
- Create a client Template that includes a Result 
- Debug options